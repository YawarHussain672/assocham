<?php

namespace App\Services;

use ZipArchive;
use SimpleXMLElement;
use Exception;

class ExcelReader
{
    /**
     * Parses an uploaded file (CSV, TXT, or XLSX) into an array of row arrays.
     * Automatically detects and converts form-style layouts into standard tabular format.
     *
     * @param string $filePath
     * @param string|null $originalExtension
     * @return array<int, array<int, string>>
     */
    public static function parseRows(string $filePath, ?string $originalExtension = null): array
    {
        $ext = strtolower($originalExtension ?: pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'xlsx') {
            return self::parseXlsx($filePath);
        }

        $rows = self::parseCsv($filePath);
        if (self::isFormLayout($rows)) {
            return self::convertFormLayoutToTabular($rows);
        }

        return $rows;
    }

    /**
     * Parse CSV or TXT file into rows.
     */
    public static function parseCsv(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return $rows;
        }

        // Handle possible UTF-8 BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        while (($data = fgetcsv($handle)) !== false) {
            // Trim each column
            $trimmed = array_map(fn ($val) => is_string($val) ? trim($val) : '', $data);
            // Skip empty rows
            if (empty(array_filter($trimmed, fn ($v) => $v !== ''))) {
                continue;
            }
            $rows[] = $trimmed;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Parse XLSX file natively using PHP ZipArchive and SimpleXML without external packages.
     * Supports single-sheet, multi-sheet, tabular, and visual form-style layouts.
     */
    public static function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            // Fallback to CSV parsing in case user renamed CSV to .xlsx
            return self::parseCsv($filePath);
        }

        // 1. Shared Strings Table (if present)
        $sharedStrings = [];
        $stringsXmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsXmlContent !== false) {
            $xml = @simplexml_load_string($stringsXmlContent);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $str = '';
                        foreach ($si->r as $r) {
                            $str .= (string) $r->t;
                        }
                        $sharedStrings[] = $str;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Discover all worksheets
        $sheetFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (preg_match('#^xl/worksheets/sheet(\d+)\.xml$#i', $stat['name'], $m)) {
                $sheetFiles[(int)$m[1]] = $stat['name'];
            }
        }
        ksort($sheetFiles);

        if (empty($sheetFiles)) {
            $zip->close();
            return [];
        }

        $parsedSheets = [];

        foreach ($sheetFiles as $sheetNum => $sheetPath) {
            $sheetXmlContent = $zip->getFromName($sheetPath);
            if ($sheetXmlContent === false) {
                continue;
            }

            $sheetXml = @simplexml_load_string($sheetXmlContent);
            if (! $sheetXml || ! isset($sheetXml->sheetData->row)) {
                continue;
            }

            $sheetRows = [];
            foreach ($sheetXml->sheetData->row as $row) {
                $rowCells = [];
                $maxColIdx = 0;

                foreach ($row->c as $cell) {
                    $cellRef = (string) $cell['r']; // e.g. "A1", "C2"
                    $colLetters = preg_replace('/[0-9]/', '', $cellRef);
                    $colIndex = self::colLetterToIndex($colLetters);
                    $maxColIdx = max($maxColIdx, $colIndex);

                    $cellType = (string) $cell['t']; // 's' = shared string, 'inlineStr' = inline string
                    $cellVal = '';

                    if ($cellType === 's') {
                        $sIndex = (int) $cell->v;
                        $cellVal = $sharedStrings[$sIndex] ?? '';
                    } elseif ($cellType === 'inlineStr' && isset($cell->is->t)) {
                        $cellVal = (string) $cell->is->t;
                    } elseif (isset($cell->v)) {
                        $cellVal = (string) $cell->v;
                    }

                    $rowCells[$colIndex] = trim($cellVal);
                }

                $denseRow = [];
                for ($c = 0; $c <= $maxColIdx; $c++) {
                    $denseRow[] = $rowCells[$c] ?? '';
                }

                if (! empty(array_filter($denseRow, fn ($v) => $v !== ''))) {
                    $sheetRows[] = $denseRow;
                }
            }

            if (! empty($sheetRows)) {
                $parsedSheets[$sheetNum] = $sheetRows;
            }
        }

        $zip->close();

        if (empty($parsedSheets)) {
            return [];
        }

        // 3. Process sheets: if any sheet is a form layout, normalize it
        $combinedRows = [];
        $hasFormSheet = false;
        $standardHeader = null;

        foreach ($parsedSheets as $sheetNum => $rows) {
            if (self::isFormLayout($rows)) {
                $hasFormSheet = true;
                $tabular = self::convertFormLayoutToTabular($rows);
                if (! empty($tabular)) {
                    if ($standardHeader === null) {
                        $standardHeader = $tabular[0];
                        $combinedRows[] = $standardHeader;
                    }
                    // Append all data rows from this form sheet
                    for ($i = 1; $i < count($tabular); $i++) {
                        $combinedRows[] = $tabular[$i];
                    }
                }
            }
        }

        // If we found and converted form sheet(s), return combined form rows
        // (This safely ignores any duplicate Sheet 2 "Import Data" that was bundled in the template)
        if ($hasFormSheet && ! empty($combinedRows)) {
            return $combinedRows;
        }

        // If no form layout was found, treat as standard tabular spreadsheet
        // Combine all tabular sheets, preserving first sheet's header
        $tabularCombined = [];
        $firstTabularHeader = null;

        foreach ($parsedSheets as $sheetNum => $rows) {
            if (empty($rows)) {
                continue;
            }

            if ($firstTabularHeader === null) {
                $firstTabularHeader = $rows[0];
                $tabularCombined[] = $firstTabularHeader;
                for ($i = 1; $i < count($rows); $i++) {
                    $tabularCombined[] = $rows[$i];
                }
            } else {
                // If subsequent sheet has identical header row, skip header
                $startIdx = 0;
                if (isset($rows[0]) && self::isHeaderRow($rows[0])) {
                    $startIdx = 1;
                }
                for ($i = $startIdx; $i < count($rows); $i++) {
                    $tabularCombined[] = $rows[$i];
                }
            }
        }

        return $tabularCombined;
    }

    /**
     * Check if a row resembles a tabular column header row.
     */
    private static function isHeaderRow(array $row): bool
    {
        $joined = strtolower(implode(' ', $row));
        return str_contains($joined, 'item') || str_contains($joined, 'pr no') || str_contains($joined, 'department');
    }

    /**
     * Detect if the given rows represent a visual form layout (like the Create PR form).
     */
    public static function isFormLayout(array $rows): bool
    {
        $limit = min(15, count($rows));
        for ($i = 0; $i < $limit; $i++) {
            $rowStr = strtolower(implode(' ', $rows[$i] ?? []));
            if (str_contains($rowStr, 'requisition header') 
                || str_contains($rowStr, 'pr number') 
                || str_contains($rowStr, 'line items')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert a visual form layout sheet into standard 13-column tabular rows.
     */
    public static function convertFormLayoutToTabular(array $rows): array
    {
        $prNumber = '';
        $prTitle = '';
        $department = '';
        $requiredBy = '';
        $purpose = '';
        $soleSource = 'No';
        $status = 'Pending';

        $itemHeaderRowIdx = null;

        foreach ($rows as $rIdx => $row) {
            $col0 = strtolower(trim((string)($row[0] ?? '')));
            $col1 = trim((string)($row[1] ?? ''));

            if (str_contains($col0, 'pr number')) {
                $prNumber = $col1;
            } elseif (str_contains($col0, 'pr title') || str_contains($col0, 'subject')) {
                $prTitle = $col1;
            } elseif (str_contains($col0, 'department')) {
                $department = $col1;
            } elseif (str_contains($col0, 'required by')) {
                $requiredBy = $col1;
            } elseif (str_contains($col0, 'purpose') || str_contains($col0, 'requisition description')) {
                $purpose = $col1;
            } elseif (str_contains($col0, 'sole source')) {
                $soleSource = $col1;
            } elseif (str_contains($col0, 'status')) {
                $status = $col1;
            }

            // Check if this row is the LINE ITEMS table header
            $rowStr = strtolower(implode(' ', $row));
            if (str_contains($rowStr, 'item name')) {
                $itemHeaderRowIdx = $rIdx;
            }
        }

        if ($itemHeaderRowIdx === null) {
            return [];
        }

        // Map column indices from the item header row
        $headerRow = $rows[$itemHeaderRowIdx];
        $colIdxItem = 1;
        $colIdxQty = 2;
        $colIdxUnit = 3;
        $colIdxCost = 4;
        $colIdxGst = 5;
        $colIdxSpecs = 6;

        foreach ($headerRow as $cIdx => $val) {
            $cClean = strtolower(preg_replace('/[^a-z]/i', '', (string)$val));
            if (str_contains($cClean, 'itemname') || $cClean === 'item') {
                $colIdxItem = $cIdx;
            } elseif (str_contains($cClean, 'qty') || str_contains($cClean, 'quantity')) {
                $colIdxQty = $cIdx;
            } elseif (str_contains($cClean, 'unit') && ! str_contains($cClean, 'cost')) {
                $colIdxUnit = $cIdx;
            } elseif (str_contains($cClean, 'cost') || str_contains($cClean, 'price') || str_contains($cClean, 'rate')) {
                $colIdxCost = $cIdx;
            } elseif (str_contains($cClean, 'gst') || str_contains($cClean, 'tax')) {
                $colIdxGst = $cIdx;
            } elseif (str_contains($cClean, 'specs') || str_contains($cClean, 'desc')) {
                $colIdxSpecs = $cIdx;
            }
        }

        $standardHeader = [
            'PR NO.', 'PR TITLE', 'DEPARTMENT', 'ITEM NAME(S)', 'QTY', 'UNIT',
            'UNIT COST', 'GST%', 'DESCRIPTION / SPECS', 'REQUIRED BY', 'PURPOSE',
            'SOLE SOURCE', 'STATUS'
        ];

        $tabularRows = [$standardHeader];

        for ($rIdx = $itemHeaderRowIdx + 1; $rIdx < count($rows); $rIdx++) {
            $row = $rows[$rIdx];
            $firstCell = strtolower(trim((string)($row[0] ?? '')));

            // Stop if reached TOTALS section
            if (str_contains($firstCell, 'total') || str_contains($firstCell, 'subtotal')) {
                break;
            }

            $itemName = trim((string)($row[$colIdxItem] ?? ''));
            if ($itemName === '') {
                // If item name is empty, skip blank template row
                continue;
            }

            $qty = trim((string)($row[$colIdxQty] ?? '1')) ?: '1';
            $unit = trim((string)($row[$colIdxUnit] ?? 'Units')) ?: 'Units';
            $cost = trim((string)($row[$colIdxCost] ?? '0'));
            $gst = trim((string)($row[$colIdxGst] ?? '18')) ?: '18';
            $specs = trim((string)($row[$colIdxSpecs] ?? ''));

            $tabularRows[] = [
                $prNumber,
                $prTitle,
                $department,
                $itemName,
                $qty,
                $unit,
                $cost,
                $gst,
                $specs,
                $requiredBy,
                $purpose,
                $soleSource,
                $status,
            ];
        }

        return $tabularRows;
    }

    /**
     * Convert Excel column letter (A, B, ..., Z, AA, AB) to 0-based index.
     */
    private static function colLetterToIndex(string $letters): int
    {
        $letters = strtoupper(trim($letters));
        $len = strlen($letters);
        $index = 0;

        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return max(0, $index - 1);
    }

    /**
     * Generate an Excel .xlsx file bytes natively using ZipArchive and OpenXML.
     *
     * @param array<int, string> $headers
     * @param array<int, array<int, string|int|float>> $rows
     * @return string Raw file content of .xlsx
     */
    public static function createXlsx(array $headers, array $rows, string $sheetName = 'Travel Requisitions'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_out_');
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            . '<sheet name="' . htmlspecialchars($sheetName, ENT_XML1, 'UTF-8') . '" sheetId="1" r:id="rId1"/>'
            . '</sheets>'
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // xl/styles.xml (Professional minimal corporate styling)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1">'
            . '<numFmt numFmtId="164" formatCode="#,##0.00"/>'
            . '</numFmts>'
            . '<fonts count="3">'
            . '<font><sz val="11"/><name val="Calibri"/><color rgb="FF0F172A"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/><color rgb="FF0F172A"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
            . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
            . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
            . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="6">'
            . '<!-- 0: default -->'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<!-- 1: Header (Dark Slate #0F172A, Bold White, Centered) -->'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="0"/></xf>'
            . '<!-- 2: Data text Left -->'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<!-- 3: Data text Center -->'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<!-- 4: Data numeric/currency Right -->'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1" applyNumberFormat="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<!-- 5: PR Number Center Bold -->'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // Calculate generous column widths based on headers and rows
        $colWidths = [];
        foreach ($headers as $colIdx => $header) {
            $headerStr = (string)$header;
            $maxLen = mb_strlen($headerStr);
            foreach ($rows as $row) {
                $valStr = (string)($row[$colIdx] ?? '');
                $len = mb_strlen($valStr);
                if ($len > $maxLen) {
                    $maxLen = $len;
                }
            }
            $cleanH = strtolower(preg_replace('/[^a-z]/i', '', $headerStr));
            $minWidth = match (true) {
                str_contains($cleanH, 'route') || str_contains($cleanH, 'itinerary') => 36,
                str_contains($cleanH, 'purpose') || str_contains($cleanH, 'reason') => 42,
                str_contains($cleanH, 'employee') || str_contains($cleanH, 'staff') => 28,
                str_contains($cleanH, 'mode') => 20,
                str_contains($cleanH, 'advance') || str_contains($cleanH, 'cost') || str_contains($cleanH, 'price') || str_contains($cleanH, 'amount') => 18,
                str_contains($cleanH, 'international') => 22,
                str_contains($cleanH, 'accommodation') || str_contains($cleanH, 'stay') => 22,
                str_contains($cleanH, 'item') || str_contains($cleanH, 'name') || str_contains($cleanH, 'desc') => 36,
                str_contains($cleanH, 'title') || str_contains($cleanH, 'subject') => 32,
                str_contains($cleanH, 'prno') || str_contains($cleanH, 'prnum') => 28,
                str_contains($cleanH, 'dept') || str_contains($cleanH, 'category') => 22,
                str_contains($cleanH, 'gst') || str_contains($cleanH, 'tax') => 14,
                str_contains($cleanH, 'date') || str_contains($cleanH, 'required') => 20,
                str_contains($cleanH, 'status') => 16,
                default => 20,
            };
            $colWidths[$colIdx] = max($minWidth, $maxLen + 4);
        }

        // xl/worksheets/sheet1.xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews>'
            . '<sheetView tabSelected="1" workbookViewId="0">'
            . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView>'
            . '</sheetViews>'
            . '<sheetFormatPr defaultRowHeight="20"/>';

        if (! empty($colWidths)) {
            $sheetXml .= '<cols>';
            foreach ($colWidths as $colIdx => $w) {
                $cIndex = $colIdx + 1;
                $sheetXml .= '<col min="' . $cIndex . '" max="' . $cIndex . '" width="' . $w . '" customWidth="1"/>';
            }
            $sheetXml .= '</cols>';
        }

        $sheetXml .= '<sheetData>';

        $rowNum = 1;

        // Write header
        if (! empty($headers)) {
            $sheetXml .= '<row r="' . $rowNum . '" ht="28" customHeight="1">';
            foreach ($headers as $colIdx => $header) {
                $colLetter = self::indexToColLetter($colIdx);
                $escaped = htmlspecialchars((string)$header, ENT_XML1, 'UTF-8');
                $sheetXml .= '<c r="' . $colLetter . $rowNum . '" t="inlineStr" s="1"><is><t>' . $escaped . '</t></is></c>';
            }
            $sheetXml .= '</row>';
            $rowNum++;
        }

        // Write data rows
        foreach ($rows as $rowData) {
            $sheetXml .= '<row r="' . $rowNum . '" ht="22" customHeight="1">';
            foreach ($rowData as $colIdx => $val) {
                $colLetter = self::indexToColLetter($colIdx);
                $headerStr = strtolower(preg_replace('/[^a-z]/i', '', (string)($headers[$colIdx] ?? '')));

                // Determine appropriate styling
                $styleIndex = 2; // Default left aligned with border
                if (str_contains($headerStr, 'prno') || str_contains($headerStr, 'prnum')) {
                    $styleIndex = 5; // PR number bold center
                } elseif (str_contains($headerStr, 'cost') || str_contains($headerStr, 'rate') || str_contains($headerStr, 'price') || str_contains($headerStr, 'amount') || str_contains($headerStr, 'advance')) {
                    $styleIndex = 4; // Right aligned currency
                } elseif (str_contains($headerStr, 'status') || str_contains($headerStr, 'date') || str_contains($headerStr, 'dept') || str_contains($headerStr, 'quantity') || str_contains($headerStr, 'unit') || str_contains($headerStr, 'mode') || str_contains($headerStr, 'international') || str_contains($headerStr, 'accommodation')) {
                    $styleIndex = 3; // Centered
                }

                if (is_numeric($val) && ! is_string($val)) {
                    $sheetXml .= '<c r="' . $colLetter . $rowNum . '" s="' . $styleIndex . '"><v>' . $val . '</v></c>';
                } elseif (is_numeric($val) && is_string($val) && ! str_starts_with($val, '0')) {
                    $sheetXml .= '<c r="' . $colLetter . $rowNum . '" s="' . $styleIndex . '"><v>' . $val . '</v></c>';
                } else {
                    $escaped = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');
                    $sheetXml .= '<c r="' . $colLetter . $rowNum . '" t="inlineStr" s="' . $styleIndex . '"><is><t>' . $escaped . '</t></is></c>';
                }
            }
            $sheetXml .= '</row>';
            $rowNum++;
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();
        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    private static function indexToColLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(($index % 26) + ord('A')) . $letter;
            $index = intdiv($index, 26) - 1;
        }
        return $letter;
    }

    /**
     * Generate a form-like two-sheet XLSX for Purchase Requisition bulk import.
     * Sheet 1 ("PR Form"): Visual form matching the Create PR UI.
     * Sheet 2 ("Import Data"): Standard 13-column tabular data (machine-readable).
     *
     * @param string $prNumber Sample PR number for the template
     * @param array  $sampleItems Array of item arrays: [item_name, qty, unit, unit_cost, gst, specs]
     * @param string $prTitle
     * @param string $department
     * @param string $requiredBy YYYY-MM-DD
     * @param string $purpose
     * @param string $soleSource
     * @param string $status
     * @return string Raw XLSX bytes
     */
    public static function createFormXlsx(
        string $prNumber,
        array $sampleItems,
        string $prTitle = '',
        string $department = '',
        string $requiredBy = '',
        string $purpose = '',
        string $soleSource = 'No',
        string $status = 'Pending'
    ): string {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_form_');
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // ── [Content_Types].xml ──────────────────────────────────────────────
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // ── _rels/.rels ──────────────────────────────────────────────────────
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // ── xl/_rels/workbook.xml.rels ───────────────────────────────────────
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // ── xl/workbook.xml ──────────────────────────────────────────────────
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            . '<sheet name="PR Form" sheetId="1" r:id="rId1"/>'
            . '<sheet name="Import Data" sheetId="2" r:id="rId2"/>'
            . '</sheets>'
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // ── xl/styles.xml ────────────────────────────────────────────────────
        // Font indices:
        //   0: default (dark, 11pt Calibri)
        //   1: bold white (for dark headers)
        //   2: bold dark (for labels / totals)
        // Fill indices (0=none, 1=gray125 required by Excel):
        //   2: dark slate  #0F172A
        //   3: light blue  #EFF6FF
        //   4: yellow      #FEFCE8
        //   5: light gray  #F1F5F9
        //   6: green dark  #166534
        //   7: medium slate #1E293B
        // Border:
        //   0: none
        //   1: thin #CBD5E1
        // cellXfs (style indices 0–11):
        //   0: default
        //   1: dark header (slate fill, bold white, centered)
        //   2: data left-aligned with border
        //   3: data centered with border
        //   4: currency right-aligned with border (#,##0.00)
        //   5: PR number bold center with border
        //   6: label (bold dark, light gray fill, left, border)
        //   7: value editable (normal, yellow fill, left, border, wrapText)
        //   8: section header green
        //   9: item column header (medium slate fill, bold white, centered)
        //  10: totals label (bold dark, right, border)
        //  11: totals value (bold dark, right, number format, border)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1">'
            . '<numFmt numFmtId="164" formatCode="#,##0.00"/>'
            . '</numFmts>'
            . '<fonts count="3">'
            . '<font><sz val="11"/><name val="Calibri"/><color rgb="FF0F172A"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/><color rgb="FF0F172A"/></font>'
            . '</fonts>'
            . '<fills count="8">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEFF6FF"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFEFCE8"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF166534"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E293B"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
            . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
            . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
            . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="12">'
            // 0: default
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            // 1: dark header (slate #0F172A, bold white, centered)
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="0"/></xf>'
            // 2: data left-aligned with border
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            // 3: data centered with border
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            // 4: currency right-aligned with border
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1" applyNumberFormat="1"><alignment horizontal="right" vertical="center"/></xf>'
            // 5: PR number bold center with border
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            // 6: label (bold dark font, light gray fill #F1F5F9, left, border)
            . '<xf numFmtId="0" fontId="2" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            // 7: value editable (normal font, yellow fill #FEFCE8, left, border, wrapText)
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            // 8: section header green (#166534 fill, bold white, centered)
            . '<xf numFmtId="0" fontId="1" fillId="6" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            // 9: item column header (medium slate #1E293B fill, bold white, centered)
            . '<xf numFmtId="0" fontId="1" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="0"/></xf>'
            // 10: totals label (bold dark, right, border)
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            // 11: totals value (bold dark, right, number format, border)
            . '<xf numFmtId="164" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1" applyNumberFormat="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // ── Helper closures ──────────────────────────────────────────────────
        $str = fn (string $v): string => htmlspecialchars($v, ENT_XML1, 'UTF-8');

        /**
         * Build an inline-string cell: <c r="A1" t="inlineStr" s="N"><is><t>...</t></is></c>
         */
        $strCell = function (string $ref, string $val, int $style) use ($str): string {
            return '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>' . $str($val) . '</t></is></c>';
        };

        /**
         * Build a numeric cell: <c r="A1" s="N"><v>123</v></c>
         */
        $numCell = function (string $ref, $val, int $style): string {
            return '<c r="' . $ref . '" s="' . $style . '"><v>' . $val . '</v></c>';
        };

        /**
         * Build a formula cell: <c r="H25" s="N"><f>...</f></c>
         */
        $fmlaCell = function (string $ref, string $formula, int $style): string {
            return '<c r="' . $ref . '" s="' . $style . '"><f>' . $formula . '</f></c>';
        };

        // ════════════════════════════════════════════════════════════════════
        // SHEET 1 — "PR Form"
        // ════════════════════════════════════════════════════════════════════
        $s1 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView tabSelected="1" workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="20"/>'
            // Column widths: A=32, B=28, C=8, D=10, E=16, F=8, G=52, H=18
            . '<cols>'
            . '<col min="1" max="1" width="32" customWidth="1"/>'
            . '<col min="2" max="2" width="28" customWidth="1"/>'
            . '<col min="3" max="3" width="8" customWidth="1"/>'
            . '<col min="4" max="4" width="10" customWidth="1"/>'
            . '<col min="5" max="5" width="16" customWidth="1"/>'
            . '<col min="6" max="6" width="8" customWidth="1"/>'
            . '<col min="7" max="7" width="52" customWidth="1"/>'
            . '<col min="8" max="8" width="18" customWidth="1"/>'
            . '</cols>'
            . '<sheetData>';

        $merges = []; // collect merge ranges

        // ── Row 1: Title banner A1:H1 (style 1 = dark slate, bold white, centered) ──
        $s1 .= '<row r="1" ht="30" customHeight="1">'
            . $strCell('A1', 'ASSOCHAM ERP — PURCHASE REQUISITION BULK IMPORT TEMPLATE', 1)
            . '</row>';
        $merges[] = 'A1:H1';

        // ── Row 2: Instructions A2:H2 (style 7 = yellow editable — reuse light blue look via style 7 italic note) ──
        // We'll use style 2 (data left) with the instructional text
        $s1 .= '<row r="2" ht="20" customHeight="1">'
            . $strCell('A2', 'Fill in the REQUISITION HEADER fields below, then add one item per row in the LINE ITEMS section. Use one sheet per PR. Upload this file to import.', 2)
            . '</row>';
        $merges[] = 'A2:H2';

        // ── Row 3: blank spacer ──
        $s1 .= '<row r="3" ht="6" customHeight="1"></row>';

        // ── Row 4: Section header A4:H4 "REQUISITION HEADER" (style 1 = dark slate) ──
        $s1 .= '<row r="4" ht="24" customHeight="1">'
            . $strCell('A4', 'REQUISITION HEADER', 1)
            . '</row>';
        $merges[] = 'A4:H4';

        // ── Rows 5–11: Header fields (label col A style 6, value B:H merged style 7) ──
        $headerFields = [
            5  => ['PR NUMBER (leave blank for auto-generate)', $prNumber],
            6  => ['PR TITLE / SUBJECT',                        $prTitle],
            7  => ['DEPARTMENT',                                $department],
            8  => ['REQUIRED BY DATE (YYYY-MM-DD)',             $requiredBy],
            9  => ['PURPOSE / DESCRIPTION',                     $purpose],
            10 => ['SOLE SOURCE (Yes / No)',                    $soleSource],
            11 => ['STATUS (Pending / Approved / Rejected)',    $status],
        ];

        foreach ($headerFields as $rowNum => $pair) {
            [$label, $value] = $pair;
            $colA = 'A' . $rowNum;
            $colB = 'B' . $rowNum;
            $s1 .= '<row r="' . $rowNum . '" ht="20" customHeight="1">'
                . $strCell($colA, $label, 6)
                . $strCell($colB, $value, 7)
                . '</row>';
            $merges[] = $colB . ':H' . $rowNum;
        }

        // ── Row 12: blank spacer ──
        $s1 .= '<row r="12" ht="6" customHeight="1"></row>';

        // ── Row 13: Section header A13:H13 "LINE ITEMS" (style 8 = green) ──
        $s1 .= '<row r="13" ht="24" customHeight="1">'
            . $strCell('A13', 'LINE ITEMS', 8)
            . '</row>';
        $merges[] = 'A13:H13';

        // ── Row 14: Column headers (style 9 = medium slate, bold white) ──
        $itemHeaders = ['#', 'ITEM NAME *', 'QTY', 'UNIT', 'UNIT COST (₹)', 'GST %', 'DESCRIPTION / SPECS', ''];
        $s1 .= '<row r="14" ht="22" customHeight="1">';
        foreach ($itemHeaders as $ci => $hdr) {
            $letter = self::indexToColLetter($ci);
            $s1 .= $strCell($letter . '14', $hdr, 9);
        }
        $s1 .= '</row>';

        // ── Rows 15 onwards: sample items then blank rows ──
        // Items start at row 15
        $firstItemRow = 15;
        $lastItemRow  = $firstItemRow + 7; // 8 slots (3 sample + 5 blank), rows 15–22

        foreach ($sampleItems as $idx => $item) {
            $rn = $firstItemRow + $idx;
            [$iName, $iQty, $iUnit, $iCost, $iGst, $iSpecs] = $item;
            $s1 .= '<row r="' . $rn . '" ht="20" customHeight="1">'
                . $numCell('A' . $rn, $idx + 1, 3)
                . $strCell('B' . $rn, (string)$iName, 2)
                . $numCell('C' . $rn, $iQty, 3)
                . $strCell('D' . $rn, (string)$iUnit, 3)
                . $numCell('E' . $rn, $iCost, 4)
                . $numCell('F' . $rn, $iGst, 3)
                . $strCell('G' . $rn, (string)$iSpecs, 2)
                . $strCell('H' . $rn, '', 2)
                . '</row>';
        }

        // Blank item rows for user to fill
        $sampleCount = count($sampleItems);
        for ($bi = $sampleCount; $bi < 8; $bi++) {
            $rn = $firstItemRow + $bi;
            $s1 .= '<row r="' . $rn . '" ht="20" customHeight="1">'
                . $strCell('A' . $rn, '', 3)
                . $strCell('B' . $rn, '', 2)
                . $strCell('C' . $rn, '', 3)
                . $strCell('D' . $rn, '', 3)
                . $strCell('E' . $rn, '', 4)
                . $strCell('F' . $rn, '', 3)
                . $strCell('G' . $rn, '', 2)
                . $strCell('H' . $rn, '', 2)
                . '</row>';
        }

        // ── Row 23: blank spacer ──
        $s1 .= '<row r="23" ht="6" customHeight="1"></row>';

        // ── Row 24: Totals section header A24:H24 (style 1 = dark slate) ──
        $s1 .= '<row r="24" ht="24" customHeight="1">'
            . $strCell('A24', 'TOTALS (Auto-Calculated)', 1)
            . '</row>';
        $merges[] = 'A24:H24';

        // ── Row 25: Subtotal formula H25=SUMPRODUCT(C15:C22,E15:E22) ──
        $s1 .= '<row r="25" ht="20" customHeight="1">'
            . $strCell('A25', 'Subtotal (Excl. GST)', 10)
            . $fmlaCell('H25', 'SUMPRODUCT(C15:C22,E15:E22)', 11)
            . '</row>';
        $merges[] = 'A25:G25';

        // ── Row 26: Estimated GST formula H26=SUMPRODUCT(C15:C22,E15:E22,F15:F22)/100 ──
        $s1 .= '<row r="26" ht="20" customHeight="1">'
            . $strCell('A26', 'Estimated GST', 10)
            . $fmlaCell('H26', 'SUMPRODUCT(C15:C22,E15:E22,F15:F22)/100', 11)
            . '</row>';
        $merges[] = 'A26:G26';

        // ── Row 27: Grand total H27=H25+H26 ──
        $s1 .= '<row r="27" ht="20" customHeight="1">'
            . $strCell('A27', 'Grand Total (GST Included)', 10)
            . $fmlaCell('H27', 'H25+H26', 11)
            . '</row>';
        $merges[] = 'A27:G27';

        $s1 .= '</sheetData>';

        // ── mergeCells must come AFTER </sheetData> ──
        if (! empty($merges)) {
            $s1 .= '<mergeCells count="' . count($merges) . '">';
            foreach ($merges as $m) {
                $s1 .= '<mergeCell ref="' . $m . '"/>';
            }
            $s1 .= '</mergeCells>';
        }

        $s1 .= '</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $s1);

        // ════════════════════════════════════════════════════════════════════
        // SHEET 2 — "Import Data" (standard 13-column flat table)
        // ════════════════════════════════════════════════════════════════════
        $s2Headers = [
            'PR NO.', 'PR TITLE', 'DEPARTMENT', 'ITEM NAME(S)', 'QTY', 'UNIT',
            'UNIT COST', 'GST%', 'DESCRIPTION / SPECS', 'REQUIRED BY', 'PURPOSE',
            'SOLE SOURCE', 'STATUS',
        ];

        $s2 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView tabSelected="0" workbookViewId="0">'
            . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="20"/>'
            . '<cols>'
            . '<col min="1"  max="1"  width="28" customWidth="1"/>'
            . '<col min="2"  max="2"  width="30" customWidth="1"/>'
            . '<col min="3"  max="3"  width="18" customWidth="1"/>'
            . '<col min="4"  max="4"  width="36" customWidth="1"/>'
            . '<col min="5"  max="5"  width="8"  customWidth="1"/>'
            . '<col min="6"  max="6"  width="10" customWidth="1"/>'
            . '<col min="7"  max="7"  width="14" customWidth="1"/>'
            . '<col min="8"  max="8"  width="8"  customWidth="1"/>'
            . '<col min="9"  max="9"  width="52" customWidth="1"/>'
            . '<col min="10" max="10" width="16" customWidth="1"/>'
            . '<col min="11" max="11" width="52" customWidth="1"/>'
            . '<col min="12" max="12" width="14" customWidth="1"/>'
            . '<col min="13" max="13" width="16" customWidth="1"/>'
            . '</cols>'
            . '<sheetData>';

        // Header row
        $s2 .= '<row r="1" ht="28" customHeight="1">';
        foreach ($s2Headers as $ci => $hdr) {
            $letter = self::indexToColLetter($ci);
            $s2 .= $strCell($letter . '1', $hdr, 1);
        }
        $s2 .= '</row>';

        // Data rows — one per sample item
        foreach ($sampleItems as $idx => $item) {
            $rn = $idx + 2;
            [$iName, $iQty, $iUnit, $iCost, $iGst, $iSpecs] = $item;
            $s2 .= '<row r="' . $rn . '" ht="22" customHeight="1">'
                . $strCell('A' . $rn, $prNumber, 5)
                . $strCell('B' . $rn, $prTitle, 2)
                . $strCell('C' . $rn, $department, 3)
                . $strCell('D' . $rn, (string)$iName, 2)
                . $numCell('E' . $rn, $iQty, 3)
                . $strCell('F' . $rn, (string)$iUnit, 3)
                . $numCell('G' . $rn, $iCost, 4)
                . $numCell('H' . $rn, $iGst, 3)
                . $strCell('I' . $rn, (string)$iSpecs, 2)
                . $strCell('J' . $rn, $requiredBy, 3)
                . $strCell('K' . $rn, $purpose, 2)
                . $strCell('L' . $rn, $soleSource, 3)
                . $strCell('M' . $rn, $status, 3)
                . '</row>';
        }

        $s2 .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet2.xml', $s2);

        $zip->close();
        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }
}

