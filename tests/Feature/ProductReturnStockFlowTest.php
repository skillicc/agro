<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ProductReturnController;
use App\Models\Product;
use App\Models\Project;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductReturnStockFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_return_decreases_product_warehouse_and_batch_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $project = Project::create([
            'name' => 'Return Project',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Return Warehouse',
            'code' => 'RET-001',
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Returnable Product',
            'type' => 'trading',
            'unit' => 'pcs',
            'buying_price' => 80,
            'selling_price' => 100,
            'stock_quantity' => 20,
            'is_active' => true,
        ]);

        $warehouse->updateStock($product->id, 20, true);

        $batch = StockBatch::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BTH-RET-001',
            'unit_price' => 80,
            'initial_quantity' => 20,
            'remaining_quantity' => 20,
            'purchase_date' => now()->subDay(),
            'source' => 'purchase',
            'status' => 'active',
        ]);

        $request = Request::create('/api/product-returns', 'POST', [
            'project_id' => $project->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'value' => 400,
            'date' => now()->toDateString(),
            'reason' => 'Returned to supplier',
        ]);
        $request->setUserResolver(fn () => $admin);

        $response = app(ProductReturnController::class)->store($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(15.0, (float) $product->fresh()->stock_quantity);
        $this->assertSame(15.0, (float) $warehouse->fresh()->getStockQuantity($product->id));
        $this->assertSame(15.0, (float) $batch->fresh()->remaining_quantity);
        $this->assertDatabaseHas('product_returns', [
            'project_id' => $project->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_deleting_product_return_restores_stock_levels(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $project = Project::create([
            'name' => 'Return Restore Project',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Return Restore Warehouse',
            'code' => 'RET-002',
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Restorable Product',
            'type' => 'trading',
            'unit' => 'pcs',
            'buying_price' => 90,
            'selling_price' => 120,
            'stock_quantity' => 12,
            'is_active' => true,
        ]);

        $warehouse->updateStock($product->id, 12, true);

        StockBatch::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BTH-RET-002',
            'unit_price' => 90,
            'initial_quantity' => 12,
            'remaining_quantity' => 12,
            'purchase_date' => now()->subDay(),
            'source' => 'purchase',
            'status' => 'active',
        ]);

        $storeRequest = Request::create('/api/product-returns', 'POST', [
            'project_id' => $project->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'value' => 360,
            'date' => now()->toDateString(),
            'reason' => 'Supplier return',
        ]);
        $storeRequest->setUserResolver(fn () => $admin);

        $storeResponse = app(ProductReturnController::class)->store($storeRequest);
        $this->assertSame(201, $storeResponse->getStatusCode());

        $returnModel = \App\Models\ProductReturn::query()->firstOrFail();

        $deleteRequest = Request::create('/api/product-returns/' . $returnModel->id, 'DELETE');
        $deleteRequest->setUserResolver(fn () => $admin);

        $deleteResponse = app(ProductReturnController::class)->destroy($deleteRequest, $returnModel);

        $this->assertSame(200, $deleteResponse->getStatusCode());
        $this->assertSame(12.0, (float) $product->fresh()->stock_quantity);
        $this->assertSame(12.0, (float) $warehouse->fresh()->getStockQuantity($product->id));
        $this->assertDatabaseMissing('product_returns', ['id' => $returnModel->id]);
    }
}
