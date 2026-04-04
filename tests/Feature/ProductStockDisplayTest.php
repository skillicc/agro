<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\StockBatchController;
use App\Models\Product;
use App\Models\StockBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductStockDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_stock_and_batch_selection_exclude_legacy_migration_adjustment(): void
    {
        $product = Product::create([
            'name' => 'Hayvit Test',
            'type' => 'trading',
            'unit' => 'pcs',
            'buying_price' => 100,
            'selling_price' => 120,
            'stock_quantity' => 250,
            'alert_quantity' => 10,
            'is_active' => true,
        ]);

        StockBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'BTH-1-20260201-0001',
            'unit_price' => 100,
            'initial_quantity' => 150,
            'remaining_quantity' => 150,
            'purchase_date' => now()->subMonth(),
            'source' => 'purchase',
            'status' => 'active',
        ]);

        StockBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'BTH-LEGACY-' . $product->id,
            'unit_price' => 100,
            'initial_quantity' => 100,
            'remaining_quantity' => 100,
            'purchase_date' => now()->subYear(),
            'source' => 'adjustment',
            'status' => 'active',
            'note' => 'Legacy stock migration - extra stock not from purchases',
        ]);

        $this->assertSame(150.0, (float) $product->fresh()->stock_quantity);

        $request = Request::create('/api/products/' . $product->id . '/batches', 'GET');
        $response = app(StockBatchController::class)->productBatches($product->fresh(), $request);

        $this->assertSame(150.0, (float) $response->getData(true)['total_quantity']);
        $this->assertCount(1, $response->getData(true)['batches']);
    }
}
