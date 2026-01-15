<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with(['project', 'warehouse', 'supplier', 'creator']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $purchases = $query->orderBy('date', 'desc')->get();

        return response()->json($purchases);
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
            'paid' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size' => 'nullable|string|max:255',
            'items.*.package_qty' => 'nullable|integer|min:1',
            'items.*.unit_per_package' => 'nullable|integer|min:1',
            'items.*.package_price' => 'nullable|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit_mrp' => 'nullable|numeric|min:0',
            'items.*.total_mrp' => 'nullable|numeric|min:0',
        ]);

        // Validate that at least one of project_id or warehouse_id is provided
        if (!$request->project_id && !$request->warehouse_id) {
            return response()->json([
                'message' => 'Either Project or Warehouse must be selected',
                'errors' => [
                    'project_id' => ['Either Project or Warehouse is required'],
                    'warehouse_id' => ['Either Project or Warehouse is required'],
                ]
            ], 422);
        }

        DB::beginTransaction();
        try {
            $purchase = Purchase::create([
                'project_id' => $request->project_id,
                'warehouse_id' => $request->warehouse_id,
                'supplier_id' => $request->supplier_id,
                'invoice_no' => 'PUR-' . date('Ymd') . '-' . str_pad(Purchase::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
                'date' => $request->date,
                'discount' => $request->discount ?? 0,
                'paid' => $request->paid ?? 0,
                'note' => $request->note,
                'created_by' => $request->user()->id,
            ]);

            foreach ($request->items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'size' => $item['size'] ?? null,
                    'package_qty' => $item['package_qty'] ?? 1,
                    'unit_per_package' => $item['unit_per_package'] ?? 1,
                    'package_price' => $item['package_price'] ?? 0,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_mrp' => $item['unit_mrp'] ?? 0,
                    'total' => $item['quantity'] * $item['unit_price'],
                    'total_mrp' => $item['total_mrp'] ?? 0,
                ]);
            }

            $purchase->calculateTotals();

            // Create payment if paid amount is provided
            if ($request->paid > 0 && $request->supplier_id) {
                SupplierPayment::create([
                    'supplier_id' => $request->supplier_id,
                    'purchase_id' => $purchase->id,
                    'amount' => $request->paid,
                    'date' => $request->date,
                    'payment_method' => 'cash',
                    'created_by' => $request->user()->id,
                ]);
            }

            DB::commit();

            return response()->json($purchase->load(['project', 'supplier', 'items.product', 'creator']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating purchase: ' . $e->getMessage()], 500);
        }
    }

    public function show(Purchase $purchase)
    {
        return response()->json($purchase->load(['project', 'warehouse', 'supplier', 'items.product', 'payments', 'creator']));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
            'paid' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size' => 'nullable|string|max:255',
            'items.*.package_qty' => 'nullable|integer|min:1',
            'items.*.unit_per_package' => 'nullable|integer|min:1',
            'items.*.package_price' => 'nullable|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit_mrp' => 'nullable|numeric|min:0',
            'items.*.total_mrp' => 'nullable|numeric|min:0',
        ]);

        // Validate that at least one of project_id or warehouse_id is provided
        if (!$request->project_id && !$request->warehouse_id) {
            return response()->json([
                'message' => 'Either Project or Warehouse must be selected',
                'errors' => [
                    'project_id' => ['Either Project or Warehouse is required'],
                    'warehouse_id' => ['Either Project or Warehouse is required'],
                ]
            ], 422);
        }

        DB::beginTransaction();
        try {
            $purchase->update([
                'project_id' => $request->project_id,
                'warehouse_id' => $request->warehouse_id,
                'supplier_id' => $request->supplier_id,
                'date' => $request->date,
                'discount' => $request->discount ?? 0,
                'paid' => $request->paid ?? 0,
                'note' => $request->note,
            ]);

            // Delete old items and create new ones
            $purchase->items()->delete();

            foreach ($request->items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'size' => $item['size'] ?? null,
                    'package_qty' => $item['package_qty'] ?? 1,
                    'unit_per_package' => $item['unit_per_package'] ?? 1,
                    'package_price' => $item['package_price'] ?? 0,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_mrp' => $item['unit_mrp'] ?? 0,
                    'total' => $item['quantity'] * $item['unit_price'],
                    'total_mrp' => $item['total_mrp'] ?? 0,
                ]);
            }

            $purchase->calculateTotals();
            DB::commit();

            return response()->json($purchase->load(['project', 'warehouse', 'supplier', 'items.product', 'creator']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating purchase: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Purchase $purchase)
    {
        DB::beginTransaction();
        try {
            $purchase->items()->delete();
            $purchase->delete();
            DB::commit();
            return response()->json(['message' => 'Purchase deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting purchase'], 500);
        }
    }

    public function addPayment(Request $request, Purchase $purchase)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        $payment = SupplierPayment::create([
            'supplier_id' => $purchase->supplier_id,
            'purchase_id' => $purchase->id,
            'amount' => $request->amount,
            'date' => $request->date,
            'payment_method' => $request->payment_method ?? 'cash',
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        $purchase->paid += $request->amount;
        $purchase->due -= $request->amount;
        $purchase->save();

        return response()->json($purchase->load(['payments']));
    }
}
