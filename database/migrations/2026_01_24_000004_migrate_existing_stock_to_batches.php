<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates stock batches from existing purchase items
     */
    public function up(): void
    {
        // Get all purchase items with their purchase date
        $purchaseItems = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->select(
                'purchase_items.id',
                'purchase_items.product_id',
                'purchase_items.quantity',
                'purchase_items.unit_price',
                'purchase_items.unit_mrp',
                'purchases.date as purchase_date',
                'purchases.warehouse_id'
            )
            ->orderBy('purchases.date', 'asc')
            ->orderBy('purchase_items.id', 'asc')
            ->get();

        // Create batches from purchase items
        foreach ($purchaseItems as $item) {
            $batchNumber = 'BTH-' . $item->product_id . '-' . date('Ymd', strtotime($item->purchase_date)) . '-' . $item->id;

            DB::table('stock_batches')->insert([
                'product_id' => $item->product_id,
                'purchase_item_id' => $item->id,
                'warehouse_id' => $item->warehouse_id,
                'batch_number' => $batchNumber,
                'unit_price' => $item->unit_price,
                'unit_mrp' => $item->unit_mrp ?? 0,
                'initial_quantity' => $item->quantity,
                'remaining_quantity' => $item->quantity, // Will be adjusted below
                'purchase_date' => $item->purchase_date,
                'source' => 'purchase',
                'status' => 'active',
                'note' => 'Migrated from existing purchase',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Now adjust remaining quantities based on current product stock
        // This uses FIFO to distribute stock across batches
        $products = DB::table('products')->get();

        foreach ($products as $product) {
            $currentStock = floatval($product->stock_quantity);

            // Get all batches for this product in FIFO order
            $batches = DB::table('stock_batches')
                ->where('product_id', $product->id)
                ->orderBy('purchase_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            // Calculate total purchased quantity
            $totalPurchased = $batches->sum('initial_quantity');

            // If current stock is less than total purchased, distribute the difference
            if ($currentStock < $totalPurchased) {
                // Amount that was sold/used
                $soldAmount = $totalPurchased - $currentStock;

                // Deduct from oldest batches first (FIFO)
                foreach ($batches as $batch) {
                    if ($soldAmount <= 0) break;

                    $deductFromThisBatch = min($soldAmount, $batch->initial_quantity);

                    $remaining = $batch->initial_quantity - $deductFromThisBatch;
                    $status = $remaining > 0 ? 'active' : 'depleted';

                    DB::table('stock_batches')
                        ->where('id', $batch->id)
                        ->update([
                            'remaining_quantity' => $remaining,
                            'status' => $status,
                        ]);

                    $soldAmount -= $deductFromThisBatch;
                }
            }

            // If current stock is more than total purchased (e.g., from productions),
            // create an adjustment batch for the extra
            if ($currentStock > $totalPurchased && $totalPurchased > 0) {
                $extraStock = $currentStock - $totalPurchased;

                // Calculate average price from existing batches
                $avgPrice = DB::table('stock_batches')
                    ->where('product_id', $product->id)
                    ->avg('unit_price');

                DB::table('stock_batches')->insert([
                    'product_id' => $product->id,
                    'batch_number' => 'BTH-LEGACY-' . $product->id,
                    'unit_price' => $avgPrice ?? $product->buying_price ?? 0,
                    'initial_quantity' => $extraStock,
                    'remaining_quantity' => $extraStock,
                    'purchase_date' => now()->subYear(), // Legacy date
                    'source' => 'adjustment',
                    'status' => 'active',
                    'note' => 'Legacy stock migration - extra stock not from purchases',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // If there's stock but no purchases, create an adjustment batch
            if ($currentStock > 0 && $totalPurchased == 0) {
                DB::table('stock_batches')->insert([
                    'product_id' => $product->id,
                    'batch_number' => 'BTH-INITIAL-' . $product->id,
                    'unit_price' => $product->buying_price ?? $product->production_cost ?? 0,
                    'initial_quantity' => $currentStock,
                    'remaining_quantity' => $currentStock,
                    'purchase_date' => now()->subYear(),
                    'source' => 'adjustment',
                    'status' => 'active',
                    'note' => 'Initial stock migration - no purchase history',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete all migrated batches
        DB::table('stock_batches')
            ->whereIn('note', [
                'Migrated from existing purchase',
                'Legacy stock migration - extra stock not from purchases',
                'Initial stock migration - no purchase history'
            ])
            ->delete();
    }
};
