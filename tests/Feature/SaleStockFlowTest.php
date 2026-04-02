<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\CustomerController;
use App\Models\Customer;
use App\Models\Land;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SaleStockFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_sale_can_be_created_without_batch_selection_and_decrements_shop_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $shopProject = Project::create([
            'name' => 'Shop Alpha',
            'type' => 'shop',
            'location' => 'Dhaka',
            'is_active' => true,
        ]);

        $shopWarehouse = Warehouse::create([
            'name' => 'Shop Alpha Warehouse',
            'code' => 'SHP-001',
            'project_id' => $shopProject->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Trading Item',
            'type' => 'trading',
            'unit' => 'pcs',
            'selling_price' => 120,
            'buying_price' => 80,
            'stock_quantity' => 20,
            'is_active' => true,
        ]);

        $shopWarehouse->updateStock($product->id, 8, true);

        $customer = Customer::create([
            'name' => 'Walk-in Customer',
            'is_active' => true,
        ]);

        $request = Request::create('/api/sales', 'POST', [
            'project_id' => $shopProject->id,
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'discount' => 0,
            'paid' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 120,
                    'batch_selections' => [],
                ],
            ],
        ]);
        $request->setUserResolver(fn () => $admin);

        $response = app(SaleController::class)->store($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('sales', ['project_id' => $shopProject->id]);
        $this->assertSame(5.0, (float) $shopWarehouse->fresh()->getStockQuantity($product->id));
        $this->assertSame(17.0, (float) $product->fresh()->stock_quantity);
    }

    public function test_sale_can_be_created_with_land_and_filtered_land_wise(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $fieldProject = Project::create([
            'name' => 'Field Tomato Project',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $land = Land::create([
            'name' => 'North Land',
            'location' => 'Gazipur Block A',
            'size' => 1.25,
            'unit' => 'acre',
            'is_active' => true,
        ]);

        $fieldProject->lands()->sync([$land->id]);

        $projectWarehouse = Warehouse::create([
            'name' => 'Field Tomato Project Warehouse',
            'code' => 'FLD-001',
            'project_id' => $fieldProject->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Own Tomato',
            'type' => 'own_production',
            'unit' => 'kg',
            'selling_price' => 60,
            'buying_price' => 35,
            'stock_quantity' => 40,
            'is_active' => true,
        ]);

        $projectWarehouse->updateStock($product->id, 12, true);

        $customer = Customer::create([
            'name' => 'Land Customer',
            'is_active' => true,
        ]);

        $request = Request::create('/api/sales', 'POST', [
            'project_id' => $fieldProject->id,
            'land_id' => $land->id,
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'discount' => 0,
            'paid' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 60,
                    'batch_selections' => [],
                ],
            ],
        ]);
        $request->setUserResolver(fn () => $admin);

        $response = app(SaleController::class)->store($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($land->id, $response->getData(true)['land']['id'] ?? null);
        $this->assertDatabaseHas('sales', ['project_id' => $fieldProject->id, 'land_id' => $land->id]);

        $filterRequest = Request::create('/api/sales', 'GET', ['land_id' => $land->id]);
        $filterRequest->setUserResolver(fn () => $admin);

        $filteredResponse = app(SaleController::class)->index($filterRequest);

        $this->assertCount(1, $filteredResponse->getData(true));
        $this->assertSame($land->id, $filteredResponse->getData(true)[0]['land']['id'] ?? null);
    }

    public function test_customer_level_payment_syncs_sale_due_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $project = Project::create([
            'name' => 'Receivable Check Project',
            'type' => 'field',
            'location' => 'Dhaka',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Receivable Check Warehouse',
            'code' => 'RCV-001',
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Payment Sync Item',
            'type' => 'own_production',
            'unit' => 'pcs',
            'selling_price' => 100,
            'buying_price' => 60,
            'stock_quantity' => 20,
            'is_active' => true,
        ]);

        $warehouse->updateStock($product->id, 5, true);

        $customer = Customer::create([
            'name' => 'Jabed',
            'is_active' => true,
        ]);

        $saleRequest = Request::create('/api/sales', 'POST', [
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'discount' => 0,
            'paid' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 100,
                    'batch_selections' => [],
                ],
            ],
        ]);
        $saleRequest->setUserResolver(fn () => $admin);

        $saleResponse = app(SaleController::class)->store($saleRequest);

        $this->assertSame(201, $saleResponse->getStatusCode());

        $saleId = $saleResponse->getData(true)['id'];

        $paymentRequest = Request::create('/api/customers/' . $customer->id . '/payment', 'POST', [
            'amount' => 300,
            'date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);
        $paymentRequest->setUserResolver(fn () => $admin);

        $paymentResponse = app(CustomerController::class)->addPayment($paymentRequest, $customer);

        $this->assertSame(201, $paymentResponse->getStatusCode());
        $this->assertSame(0.0, (float) $customer->fresh()->total_due);
        $this->assertSame(300.0, (float) $customer->fresh()->sales()->findOrFail($saleId)->paid);
        $this->assertSame(0.0, (float) $customer->fresh()->sales()->findOrFail($saleId)->due);
    }

    public function test_customer_overpayment_carries_forward_to_later_sale_due(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $project = Project::create([
            'name' => 'Carry Forward Project',
            'type' => 'field',
            'location' => 'Dhaka',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Carry Forward Warehouse',
            'code' => 'CRY-001',
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Carry Forward Item',
            'type' => 'own_production',
            'unit' => 'pcs',
            'selling_price' => 100,
            'buying_price' => 60,
            'stock_quantity' => 30,
            'is_active' => true,
        ]);

        $warehouse->updateStock($product->id, 10, true);

        $customer = Customer::create([
            'name' => 'Carry Forward Customer',
            'is_active' => true,
        ]);

        $firstSaleRequest = Request::create('/api/sales', 'POST', [
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'date' => '2026-03-10',
            'discount' => 0,
            'paid' => 0,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
                'batch_selections' => [],
            ]],
        ]);
        $firstSaleRequest->setUserResolver(fn () => $admin);
        $firstSale = app(SaleController::class)->store($firstSaleRequest)->getData(true);

        $secondSaleRequest = Request::create('/api/sales', 'POST', [
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'date' => '2026-03-11',
            'discount' => 0,
            'paid' => 0,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 100,
                'batch_selections' => [],
            ]],
        ]);
        $secondSaleRequest->setUserResolver(fn () => $admin);
        $secondSale = app(SaleController::class)->store($secondSaleRequest)->getData(true);

        $overpaymentRequest = Request::create('/api/sales/' . $firstSale['id'] . '/payment', 'POST', [
            'amount' => 300,
            'date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);
        $overpaymentRequest->setUserResolver(fn () => $admin);
        app(SaleController::class)->addPayment($overpaymentRequest, \App\Models\Sale::findOrFail($firstSale['id']));

        $customer->fresh()->updateBalance();

        $this->assertSame(0.0, (float) $customer->fresh()->total_due);
        $this->assertSame(0.0, (float) $customer->fresh()->sales()->findOrFail($firstSale['id'])->due);
        $this->assertSame(0.0, (float) $customer->fresh()->sales()->findOrFail($secondSale['id'])->due);
    }

    public function test_central_warehouse_sale_requires_batch_selection_for_trading_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $fieldProject = Project::create([
            'name' => 'Field Project',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Central Warehouse',
            'code' => 'WH-001',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Batch Controlled Item',
            'type' => 'trading',
            'unit' => 'pcs',
            'selling_price' => 150,
            'buying_price' => 100,
            'stock_quantity' => 15,
            'is_active' => true,
        ]);

        $warehouse->updateStock($product->id, 10, true);

        $customer = Customer::create([
            'name' => 'Retail Customer',
            'is_active' => true,
        ]);

        $request = Request::create('/api/sales', 'POST', [
            'project_id' => $fieldProject->id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'discount' => 0,
            'paid' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 150,
                    'batch_selections' => [],
                ],
            ],
        ]);
        $request->setUserResolver(fn () => $admin);

        $response = app(SaleController::class)->store($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Error creating sale: Batch selection is required for Batch Controlled Item', $response->getData(true)['message']);
        $this->assertSame(10.0, (float) $warehouse->fresh()->getStockQuantity($product->id));
    }
}