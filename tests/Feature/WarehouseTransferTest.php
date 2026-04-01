<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\WarehouseController;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class WarehouseTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_to_shop_transfer_moves_stock_and_creates_project_warehouse(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $shop = Project::create([
            'name' => 'Banani Shop',
            'type' => 'shop',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Central Warehouse',
            'code' => 'WH-CENTRAL',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Fertilizer',
            'unit' => 'bag',
            'buying_price' => 100,
            'selling_price' => 120,
            'stock_quantity' => 50,
            'alert_quantity' => 5,
            'is_active' => true,
        ]);

        $warehouse->updateStock($product->id, 12);

        $request = $this->makeAuthenticatedRequest([
            'from_warehouse_id' => $warehouse->id,
            'project_id' => $shop->id,
            'date' => '2026-04-01',
            'note' => 'Dispatch for retail',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                ],
            ],
        ], $user);

        $response = app(WarehouseController::class)->warehouseToShopTransfer($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('completed', $response->getData(true)['transfer']['status']);

        $shopWarehouse = Warehouse::where('project_id', $shop->id)->first();

        $this->assertNotNull($shopWarehouse);
        $this->assertSame('7.00', $warehouse->fresh()->stocks()->where('product_id', $product->id)->first()->quantity);
        $this->assertSame('5.00', $shopWarehouse->stocks()->where('product_id', $product->id)->first()->quantity);
    }

    public function test_shop_to_warehouse_transfer_moves_stock_from_shop_warehouse(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $shop = Project::create([
            'name' => 'Gulshan Shop',
            'type' => 'shop',
            'is_active' => true,
        ]);

        $shopWarehouse = Warehouse::create([
            'name' => 'Gulshan Shop Warehouse',
            'code' => 'PW-200',
            'project_id' => $shop->id,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Reserve Warehouse',
            'code' => 'WH-RESERVE',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Seed Pack',
            'unit' => 'piece',
            'buying_price' => 20,
            'selling_price' => 30,
            'stock_quantity' => 0,
            'alert_quantity' => 5,
            'is_active' => true,
        ]);

        $shopWarehouse->updateStock($product->id, 9);

        $request = $this->makeAuthenticatedRequest([
            'from_project_id' => $shop->id,
            'to_warehouse_id' => $warehouse->id,
            'date' => '2026-04-01',
            'note' => 'Return to main storage',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                ],
            ],
        ], $user);

        $response = app(WarehouseController::class)->shopToWarehouseTransfer($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('completed', $response->getData(true)['transfer']['status']);

        $this->assertSame('5.00', $shopWarehouse->fresh()->stocks()->where('product_id', $product->id)->first()->quantity);
        $this->assertSame('4.00', $warehouse->fresh()->stocks()->where('product_id', $product->id)->first()->quantity);
    }

    private function makeAuthenticatedRequest(array $payload, User $user): Request
    {
        $request = Request::create('/', 'POST', $payload);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}