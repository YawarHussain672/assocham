<?php

namespace App\Http\Controllers;

use App\Models\ItemCatalog;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $canManage = in_array($request->user()->role, [Roles::ADMIN_HEAD, Roles::BUYER], true);

        $items = ItemCatalog::orderBy('expense_type', 'desc')->orderBy('item_code')->get();

        $catalogCounts = [
            'total' => $items->count(),
            'active' => $items->where('is_active', true)->count(),
            'opex' => $items->where('expense_type', 'opex')->count(),
            'copex' => $items->filter(fn ($i) => $i->isCopex())->count(),
            'capex' => $items->filter(fn ($i) => $i->isCopex())->count(),
        ];

        $catalogCategories = $items->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('pages.item-catalogs-index', [
            'itemCatalogs' => $items,
            'catalogCounts' => $catalogCounts,
            'catalogCategories' => $catalogCategories,
            'canManageBudgets' => $canManage,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(
            in_array($request->user()->role, [Roles::ADMIN_HEAD, Roles::BUYER], true),
            403,
            'Only the Admin Head or Buyer can configure item catalog and fixed pricing.'
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'item_code' => ['nullable', 'string', 'max:50', 'unique:item_catalogs,item_code'],
            'category' => ['nullable', 'string', 'max:100'],
            'expense_type' => ['nullable', 'string', 'in:opex,copex,capex'],
            'fixed_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'gst_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (empty($data['item_code'])) {
            $rawType = strtolower((string) ($data['expense_type'] ?? ''));
            $prefix = in_array($rawType, ['capex', 'copex'], true) ? 'CAPEX' : ($rawType === 'opex' ? 'OPEX' : 'ITEM');
            $count = ItemCatalog::where('expense_type', $data['expense_type'] ?? null)->count() + 1;
            do {
                $code = $prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
                $count++;
            } while (ItemCatalog::where('item_code', $code)->exists());
            $data['item_code'] = $code;
        }

        $data['expense_type'] = ! empty($data['expense_type']) ? strtolower($data['expense_type']) : null;
        $data['unit'] = $data['unit'] ?: 'Units';
        $data['is_active'] = $request->boolean('is_active', true);
        $data['created_by'] = $request->user()->id;

        $item = ItemCatalog::create($data);

        $typeLabel = $item->isCopex() ? 'CapEx' : ($item->expense_type ? 'OpEx' : 'Unclassified');
        $item->writeAuditEntry(
            'created',
            "Master Item '{$item->name}' ({$item->item_code}) configured by {$request->user()->name} with fixed price ₹" . number_format((float) $item->fixed_price, 2) . " ({$typeLabel})."
        );

        return $this->redirectAfterAction(
            $request,
            "Master item '{$item->name}' ({$item->item_code}) successfully configured with fixed price ₹" . number_format((float) $item->fixed_price, 2) . '.'
        );
    }

    public function update(Request $request, ItemCatalog $itemCatalog): RedirectResponse
    {
        abort_unless(
            in_array($request->user()->role, [Roles::ADMIN_HEAD, Roles::BUYER], true),
            403,
            'Only the Admin Head or Buyer can modify item catalog and fixed pricing.'
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'item_code' => ['nullable', 'string', 'max:50', 'unique:item_catalogs,item_code,' . $itemCatalog->id],
            'category' => ['nullable', 'string', 'max:100'],
            'expense_type' => ['nullable', 'string', 'in:opex,copex,capex'],
            'fixed_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'gst_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['expense_type'] = ! empty($data['expense_type']) ? strtolower($data['expense_type']) : null;
        $data['unit'] = $data['unit'] ?: 'Units';
        $data['is_active'] = $request->boolean('is_active', true);

        $oldPrice = (float) $itemCatalog->fixed_price;
        $oldType = $itemCatalog->expense_type;

        $itemCatalog->update($data);

        $priceNote = ($oldPrice != (float) $itemCatalog->fixed_price)
            ? " Fixed price changed from ₹" . number_format($oldPrice, 2) . " to ₹" . number_format((float) $itemCatalog->fixed_price, 2) . "."
            : "";

        $itemCatalog->writeAuditEntry(
            'updated',
            "Master item '{$itemCatalog->name}' ({$itemCatalog->item_code}) updated by {$request->user()->name}." . $priceNote
        );

        return $this->redirectAfterAction($request, "Master item '{$itemCatalog->name}' successfully updated.");
    }

    public function destroy(Request $request, ItemCatalog $itemCatalog): RedirectResponse
    {
        abort_unless(
            in_array($request->user()->role, [Roles::ADMIN_HEAD, Roles::BUYER], true),
            403,
            'Only the Admin Head or Buyer can remove items from catalog.'
        );

        $name = $itemCatalog->name;

        if ($itemCatalog->requisitionItems()->exists()) {
            // Soft deactivate to maintain integrity of linked PR items
            $itemCatalog->update(['is_active' => false]);
            $itemCatalog->writeAuditEntry(
                'deactivated',
                "Master item '{$name}' deactivated by {$request->user()->name} due to existing requisition references."
            );

            return $this->redirectAfterAction(
                $request,
                "Master item '{$name}' has historical PR references, so its status was set to Inactive instead of deleting."
            );
        }

        $itemCatalog->delete();

        return $this->redirectAfterAction($request, "Master item '{$name}' successfully removed from catalog.");
    }

    protected function redirectAfterAction(Request $request, string $message): RedirectResponse
    {
        $referer = $request->header('referer');
        if ($referer && str_contains($referer, 'item-catalogs')) {
            return redirect()->route('item-catalogs.index')->with('status', $message);
        }

        return redirect()->route('checks-balances', ['view' => 'items'])->with('status', $message);
    }

    public function apiList(Request $request): JsonResponse
    {
        $items = ItemCatalog::active()
            ->orderBy('expense_type')
            ->orderBy('category')
            ->orderBy('name')
            ->get([
                'id',
                'item_code',
                'name',
                'category',
                'expense_type',
                'fixed_price',
                'unit',
                'gst_percent',
                'description',
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }
}
