<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ProductReturnBatch;
use App\Models\StockBatch;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductReturn::with(['project', 'warehouse', 'product', 'creator']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        return response()->json($query->orderBy('date', 'desc')->get());
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        DB::beginTransaction();
        try {
            $product = Product::findOrFail($data['product_id']);
            $sourceWarehouse = $this->resolveSourceWarehouse($data['project_id'] ?? null, $data['warehouse_id'] ?? null);

            $this->ensureStockAvailable($product, (float) $data['quantity'], $sourceWarehouse);

            $productReturn = ProductReturn::create([
                ...$data,
                'created_by' => $request->user()->id,
            ]);

            $this->applyInventoryReduction($productReturn, $product, $sourceWarehouse);

            DB::commit();

            return response()->json($this->loadRelations($productReturn), 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error creating product return: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(ProductReturn $productReturn)
    {
        return response()->json($this->loadRelations($productReturn));
    }

    public function update(Request $request, ProductReturn $productReturn)
    {
        $data = $this->validateRequest($request);

        DB::beginTransaction();
        try {
            $this->restoreInventory($productReturn);

            $productReturn->update($data);

            $product = Product::findOrFail($productReturn->product_id);
            $sourceWarehouse = $this->resolveSourceWarehouse($productReturn->project_id, $productReturn->warehouse_id);

            $this->ensureStockAvailable($product, (float) $productReturn->quantity, $sourceWarehouse);
            $this->applyInventoryReduction($productReturn, $product, $sourceWarehouse);

            DB::commit();

            return response()->json($this->loadRelations($productReturn->fresh()));
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error updating product return: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, ProductReturn $productReturn)
    {
        DB::beginTransaction();
        try {
            $this->restoreInventory($productReturn);
            $productReturn->delete();

            DB::commit();

            return response()->json(['message' => 'Product return deleted successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error deleting product return: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function validateRequest(Request $request): array
    {
        $data = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'value' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        if (empty($data['project_id']) && empty($data['warehouse_id'])) {
            throw ValidationException::withMessages([
                'project_id' => 'Either Project or Warehouse is required.',
                'warehouse_id' => 'Either Project or Warehouse is required.',
            ]);
        }

        return $data;
    }

    private function loadRelations(ProductReturn $productReturn): ProductReturn
    {
        return $productReturn->load(['project', 'warehouse', 'product', 'creator', 'batches.stockBatch']);
    }

    private function resolveSourceWarehouse(?int $projectId, ?int $warehouseId): ?Warehouse
    {
        if ($warehouseId) {
            return Warehouse::with('project')->find($warehouseId);
        }

        if ($projectId) {
            return Warehouse::with('project')->where('project_id', $projectId)->first();
        }

        return null;
    }

    private function ensureStockAvailable(Product $product, float $quantity, ?Warehouse $sourceWarehouse): void
    {
        $globalStock = (float) ($product->stock_quantity ?? 0);

        if ($globalStock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Insufficient stock for {$product->name}. Available: {$globalStock}, Requested: {$quantity}",
            ]);
        }

        if ($sourceWarehouse) {
            $warehouseStock = (float) $sourceWarehouse->getStockQuantity($product->id);

            if ($warehouseStock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock in {$sourceWarehouse->name} for {$product->name}. Available: {$warehouseStock}, Requested: {$quantity}",
                ]);
            }
        }
    }

    private function applyInventoryReduction(ProductReturn $productReturn, Product $product, ?Warehouse $sourceWarehouse): void
    {
        $quantity = (float) $productReturn->quantity;
        $remainingQuantity = $quantity;

        $requiresBatchTracking = !$product->isOwnProduction() && optional($sourceWarehouse?->project)->type !== 'shop';

        $batchQuery = StockBatch::query()
            ->where('product_id', $product->id)
            ->available()
            ->excludeLegacyMigrationAdjustments();

        if ($sourceWarehouse) {
            $batchQuery->where('warehouse_id', $sourceWarehouse->id);
        }

        $batches = $batchQuery->fifo()->lockForUpdate()->get();

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $deduction = min($remainingQuantity, (float) $batch->remaining_quantity);

            if ($deduction <= 0) {
                continue;
            }

            ProductReturnBatch::create([
                'product_return_id' => $productReturn->id,
                'stock_batch_id' => $batch->id,
                'quantity' => $deduction,
                'cost_price' => $batch->unit_price,
            ]);

            $batch->decrement('remaining_quantity', $deduction);
            $batch->refresh()->updateStatus();

            $remainingQuantity -= $deduction;
        }

        if ($requiresBatchTracking && $remainingQuantity > 0.0001) {
            throw ValidationException::withMessages([
                'quantity' => "Insufficient batch stock for {$product->name}. Short by {$remainingQuantity}.",
            ]);
        }

        $product->decrement('stock_quantity', $quantity);

        if ($sourceWarehouse) {
            $sourceWarehouse->updateStock($product->id, $quantity, false);
        }
    }

    private function restoreInventory(ProductReturn $productReturn): void
    {
        $product = Product::find($productReturn->product_id);
        $sourceWarehouse = $this->resolveSourceWarehouse($productReturn->project_id, $productReturn->warehouse_id);
        $quantity = (float) $productReturn->quantity;

        if ($product) {
            $product->increment('stock_quantity', $quantity);
        }

        if ($sourceWarehouse) {
            $sourceWarehouse->updateStock($productReturn->product_id, $quantity, true);
        }

        foreach ($productReturn->batches as $batchAllocation) {
            $stockBatch = $batchAllocation->stockBatch;

            if ($stockBatch) {
                $stockBatch->increment('remaining_quantity', $batchAllocation->quantity);
                $stockBatch->refresh()->updateStatus();
            }
        }

        $productReturn->batches()->delete();
    }
}
