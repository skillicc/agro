<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\StockBatch;
use App\Models\CustomerPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['project', 'customer', 'creator']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $sales = $query->orderBy('date', 'desc')->get();

        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'customer_id' => 'nullable|exists:customers,id',
            'date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
            'paid' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_selections' => 'nullable|array',
            'items.*.batch_selections.*.batch_id' => 'required_with:items.*.batch_selections|exists:stock_batches,id',
            'items.*.batch_selections.*.quantity' => 'required_with:items.*.batch_selections|numeric|min:0.01',
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
            $sale = Sale::create([
                'project_id' => $request->project_id,
                'warehouse_id' => $request->warehouse_id,
                'customer_id' => $request->customer_id,
                'challan_no' => $request->challan_no ?? ('SAL-' . date('Ymd') . '-' . str_pad(Sale::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT)),
                'date' => $request->date,
                'discount' => $request->discount ?? 0,
                'paid' => $request->paid ?? 0,
                'note' => $request->note,
                'created_by' => $request->user()->id,
            ]);

            foreach ($request->items as $item) {
                $costPrice = null;
                $totalCost = 0;

                // Calculate cost price from batch selections
                if (!empty($item['batch_selections'])) {
                    foreach ($item['batch_selections'] as $selection) {
                        $batch = StockBatch::find($selection['batch_id']);
                        if ($batch) {
                            $totalCost += $selection['quantity'] * $batch->unit_price;
                        }
                    }
                    $costPrice = $totalCost / $item['quantity'];
                }

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $costPrice,
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);

                // Process batch selections (deduct from batches)
                if (!empty($item['batch_selections'])) {
                    foreach ($item['batch_selections'] as $selection) {
                        $batch = StockBatch::find($selection['batch_id']);
                        if (!$batch) {
                            throw new \Exception("Batch not found: {$selection['batch_id']}");
                        }

                        if ($batch->remaining_quantity < $selection['quantity']) {
                            throw new \Exception("Insufficient stock in batch {$batch->batch_number}. Available: {$batch->remaining_quantity}, Requested: {$selection['quantity']}");
                        }

                        // Create sale item batch record
                        SaleItemBatch::create([
                            'sale_item_id' => $saleItem->id,
                            'stock_batch_id' => $batch->id,
                            'quantity' => $selection['quantity'],
                            'cost_price' => $batch->unit_price,
                        ]);

                        // Deduct from batch
                        $batch->decrement('remaining_quantity', $selection['quantity']);
                        $batch->updateStatus();
                    }
                }
            }

            $sale->calculateTotals();

            // Create payment if paid amount is provided
            if ($request->paid > 0 && $request->customer_id) {
                CustomerPayment::create([
                    'customer_id' => $request->customer_id,
                    'sale_id' => $sale->id,
                    'amount' => $request->paid,
                    'date' => $request->date,
                    'payment_method' => 'cash',
                    'created_by' => $request->user()->id,
                ]);
            }

            DB::commit();

            return response()->json($sale->load(['project', 'customer', 'items.product', 'items.batches.stockBatch', 'creator']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating sale: ' . $e->getMessage()], 500);
        }
    }

    public function show(Sale $sale)
    {
        return response()->json($sale->load(['project', 'customer', 'items.product', 'items.batches.stockBatch', 'payments', 'creator']));
    }

    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'customer_id' => 'nullable|exists:customers,id',
            'date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
            'paid' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_selections' => 'nullable|array',
            'items.*.batch_selections.*.batch_id' => 'required_with:items.*.batch_selections|exists:stock_batches,id',
            'items.*.batch_selections.*.quantity' => 'required_with:items.*.batch_selections|numeric|min:0.01',
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
            $sale->update([
                'project_id' => $request->project_id,
                'warehouse_id' => $request->warehouse_id,
                'customer_id' => $request->customer_id,
                'date' => $request->date,
                'discount' => $request->discount ?? 0,
                'paid' => $request->paid ?? 0,
                'note' => $request->note,
            ]);

            // Delete old items (this will restore batch quantities via SaleItem's deleted event)
            $sale->items()->delete();

            foreach ($request->items as $item) {
                $costPrice = null;
                $totalCost = 0;

                // Calculate cost price from batch selections
                if (!empty($item['batch_selections'])) {
                    foreach ($item['batch_selections'] as $selection) {
                        $batch = StockBatch::find($selection['batch_id']);
                        if ($batch) {
                            $totalCost += $selection['quantity'] * $batch->unit_price;
                        }
                    }
                    $costPrice = $totalCost / $item['quantity'];
                }

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $costPrice,
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);

                // Process batch selections (deduct from batches)
                if (!empty($item['batch_selections'])) {
                    foreach ($item['batch_selections'] as $selection) {
                        $batch = StockBatch::find($selection['batch_id']);
                        if (!$batch) {
                            throw new \Exception("Batch not found: {$selection['batch_id']}");
                        }

                        if ($batch->remaining_quantity < $selection['quantity']) {
                            throw new \Exception("Insufficient stock in batch {$batch->batch_number}. Available: {$batch->remaining_quantity}, Requested: {$selection['quantity']}");
                        }

                        // Create sale item batch record
                        SaleItemBatch::create([
                            'sale_item_id' => $saleItem->id,
                            'stock_batch_id' => $batch->id,
                            'quantity' => $selection['quantity'],
                            'cost_price' => $batch->unit_price,
                        ]);

                        // Deduct from batch
                        $batch->decrement('remaining_quantity', $selection['quantity']);
                        $batch->updateStatus();
                    }
                }
            }

            $sale->calculateTotals();
            DB::commit();

            return response()->json($sale->load(['project', 'warehouse', 'customer', 'items.product', 'items.batches.stockBatch', 'creator']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating sale: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Sale $sale)
    {
        DB::beginTransaction();
        try {
            $sale->items()->delete();
            $sale->delete();
            DB::commit();
            return response()->json(['message' => 'Sale deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting sale'], 500);
        }
    }

    public function addPayment(Request $request, Sale $sale)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        $payment = CustomerPayment::create([
            'customer_id' => $sale->customer_id,
            'sale_id' => $sale->id,
            'amount' => $request->amount,
            'date' => $request->date,
            'payment_method' => $request->payment_method ?? 'cash',
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        $sale->paid += $request->amount;
        $sale->due -= $request->amount;
        $sale->save();

        return response()->json($sale->load(['payments']));
    }
}
