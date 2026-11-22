<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.manual-testing-guide')
    ->setPaper('a4', 'portrait')
    ->setOptions([
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true,
        'defaultFont' => 'DejaVu Sans',
    ]);

$output = $pdf->output();

$publicPath = public_path('ASSOCHAM_ERP_Manual_Testing_Guide.pdf');
$basePath = base_path('ASSOCHAM_ERP_Manual_Testing_Guide.pdf');

file_put_contents($publicPath, $output);
file_put_contents($basePath, $output);

echo "PDF Generated Successfully!\n";
echo "Public Path: " . $publicPath . " (" . filesize($publicPath) . " bytes)\n";
echo "Base Path: " . $basePath . " (" . filesize($basePath) . " bytes)\n";
