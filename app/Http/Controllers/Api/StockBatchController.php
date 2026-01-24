<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockBatch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockBatchController extends Controller
{
    /**
     * Get all stock batches with filtering
     */
    public function index(Request $request)
    {
        $query = StockBatch::with(['product', 'warehouse', 'purchaseItem.purchase']);

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->only_available) {
            $query->available();
        }

        $batches = $query->orderBy('purchase_date', 'desc')->get();

        return response()->json($batches);
    }

    /**
     * Get single stock batch details
     */
    public function show(StockBatch $stockBatch)
    {
        return response()->json($stockBatch->load([
            'product',
            'warehouse',
            'purchaseItem.purchase',
            'saleItemBatches.saleItem.sale'
        ]));
    }

    /**
     * Get available batches for a product (for sales selection)
     */
    public function productBatches(Product $product, Request $request)
    {
        $query = $product->stockBatches()->available();

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $batches = $query->fifo()->get();

        // Also return grouped by price view
        $byPriceQuery = $product->stockBatches()->available();
        if ($request->warehouse_id) {
            $byPriceQuery->where('warehouse_id', $request->warehouse_id);
        }

        $byPrice = $byPriceQuery
            ->select('unit_price', DB::raw('SUM(remaining_quantity) as quantity'))
            ->groupBy('unit_price')
            ->orderBy('unit_price')
            ->get();

        return response()->json([
            'batches' => $batches,
            'by_price' => $byPrice,
            'total_quantity' => $batches->sum('remaining_quantity'),
        ]);
    }

    /**
     * Manual stock adjustment (create batch from adjustment)
     */
    public function adjust(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $batch = StockBatch::create([
            'product_id' => $request->product_id,
            'warehouse_id' => $request->warehouse_id,
            'batch_number' => StockBatch::generateBatchNumber($request->product_id),
            'unit_price' => $request->unit_price,
            'initial_quantity' => $request->quantity,
            'remaining_quantity' => $request->quantity,
            'purchase_date' => now(),
            'source' => 'adjustment',
            'status' => 'active',
            'note' => $request->note,
        ]);

        // Update global stock
        $batch->product->increment('stock_quantity', $request->quantity);

        // Update warehouse stock if applicable
        if ($request->warehouse_id) {
            \App\Models\WarehouseStock::updateOrCreate(
                [
                    'warehouse_id' => $request->warehouse_id,
                    'product_id' => $request->product_id,
                ],
                []
            )->increment('quantity', $request->quantity);
        }

        return response()->json($batch->load(['product', 'warehouse']), 201);
    }

    /**
     * Deduct from a specific batch (manual selection)
     */
    public function deduct(StockBatch $stockBatch, Request $request)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'note' => 'nullable|string',
        ]);

        if ($stockBatch->remaining_quantity < $request->quantity) {
            return response()->json([
                'message' => "Batch {$stockBatch->batch_number} only has {$stockBatch->remaining_quantity} units available."
            ], 422);
        }

        $stockBatch->decrement('remaining_quantity', $request->quantity);
        $stockBatch->updateStatus();

        // Update global stock
        $stockBatch->product->decrement('stock_quantity', $request->quantity);

        // Update warehouse stock if applicable
        if ($stockBatch->warehouse_id) {
            $warehouseStock = \App\Models\WarehouseStock::where('warehouse_id', $stockBatch->warehouse_id)
                ->where('product_id', $stockBatch->product_id)
                ->first();

            if ($warehouseStock) {
                $warehouseStock->decrement('quantity', $request->quantity);
            }
        }

        return response()->json($stockBatch->fresh());
    }

    /**
     * Get stock summary by price for all products
     */
    public function stockByPrice(Request $request)
    {
        $query = StockBatch::available()
            ->with('product:id,name,unit')
            ->select(
                'product_id',
                'unit_price',
                DB::raw('SUM(remaining_quantity) as quantity'),
                DB::raw('SUM(remaining_quantity * unit_price) as total_value')
            )
            ->groupBy('product_id', 'unit_price')
            ->orderBy('product_id')
            ->orderBy('unit_price');

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        return response()->json($query->get());
    }
}
