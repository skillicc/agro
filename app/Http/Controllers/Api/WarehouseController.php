<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\StockTransfer;
use App\Models\Project;
use App\Models\Product;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::with(['project', 'stocks.product'])
            ->withCount('stocks')
            ->get();

        return response()->json($warehouses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'manager_name' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $warehouse = Warehouse::create($request->all());

        return response()->json($warehouse, 201);
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load(['project', 'stocks.product']);

        return response()->json($warehouse);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'manager_name' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $warehouse->update($request->all());

        return response()->json($warehouse);
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return response()->json(null, 204);
    }

    public function toggleStatus(Warehouse $warehouse)
    {
        $warehouse->update(['is_active' => !$warehouse->is_active]);

        return response()->json($warehouse);
    }

    // Get stock levels for a warehouse
    public function stocks(Warehouse $warehouse)
    {
        $stocks = $warehouse->stocks()
            ->with('product.category')
            ->get();

        return response()->json($stocks);
    }

    // Update stock manually (inventory adjustment)
    public function updateStock(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric',
            'min_quantity' => 'nullable|numeric|min:0',
        ]);

        $stock = WarehouseStock::updateOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'product_id' => $request->product_id,
            ],
            [
                'quantity' => $request->quantity,
                'min_quantity' => $request->min_quantity ?? 0,
            ]
        );

        return response()->json($stock->load('product'));
    }

    // Get low stock products
    public function lowStock(Warehouse $warehouse)
    {
        $lowStocks = $warehouse->getLowStockProducts();

        return response()->json($lowStocks);
    }

    // Stock Transfers
    public function transfers(Request $request)
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'items.product', 'creator']);

        if ($request->warehouse_id) {
            $query->where(function ($q) use ($request) {
                $q->where('from_warehouse_id', $request->warehouse_id)
                    ->orWhere('to_warehouse_id', $request->warehouse_id);
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $transfers = $query->orderBy('date', 'desc')->get();

        return response()->json($transfers);
    }

    public function createTransfer(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'date' => 'required|date',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $transfer = StockTransfer::create([
            'from_warehouse_id' => $request->from_warehouse_id,
            'to_warehouse_id' => $request->to_warehouse_id,
            'date' => $request->date,
            'note' => $request->note,
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        foreach ($request->items as $item) {
            $transfer->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return response()->json($transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']), 201);
    }

    public function showTransfer(StockTransfer $transfer)
    {
        $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'creator', 'approver']);

        return response()->json($transfer);
    }

    public function completeTransfer(Request $request, StockTransfer $transfer)
    {
        if ($transfer->status === 'completed') {
            return response()->json(['message' => 'Transfer already completed'], 400);
        }

        if ($transfer->status === 'cancelled') {
            return response()->json(['message' => 'Transfer has been cancelled'], 400);
        }

        // Check if source warehouse has enough stock
        foreach ($transfer->items as $item) {
            $sourceStock = $transfer->fromWarehouse->getStockQuantity($item->product_id);
            if ($sourceStock < $item->quantity) {
                return response()->json([
                    'message' => "Insufficient stock for product: {$item->product->name}. Available: {$sourceStock}, Required: {$item->quantity}"
                ], 400);
            }
        }

        $transfer->complete($request->user()->id);

        return response()->json($transfer->fresh(['fromWarehouse', 'toWarehouse', 'items.product']));
    }

    public function cancelTransfer(StockTransfer $transfer)
    {
        if ($transfer->status === 'completed') {
            return response()->json(['message' => 'Cannot cancel completed transfer'], 400);
        }

        $transfer->cancel();

        return response()->json($transfer);
    }

    // Summary for dashboard
    public function summary()
    {
        $warehouses = Warehouse::where('is_active', true)
            ->withCount('stocks')
            ->get();

        $totalStock = WarehouseStock::sum('quantity');
        $lowStockCount = WarehouseStock::whereRaw('quantity <= min_quantity')->count();
        $pendingTransfers = StockTransfer::where('status', 'pending')->count();

        return response()->json([
            'warehouses' => $warehouses,
            'total_stock_items' => $totalStock,
            'low_stock_count' => $lowStockCount,
            'pending_transfers' => $pendingTransfers,
        ]);
    }

    // Transfer stock from warehouse to a specific project
    public function transferToProject(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'project_id' => 'required|exists:projects,id',
            'date' => 'required|date',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $warehouse = Warehouse::findOrFail($request->from_warehouse_id);
        $project = Project::findOrFail($request->project_id);

        // Check if warehouse has enough stock
        foreach ($request->items as $item) {
            $stock = $warehouse->getStockQuantity($item['product_id']);
            if ($stock < $item['quantity']) {
                $product = Product::find($item['product_id']);
                return response()->json([
                    'message' => "Insufficient stock for {$product->name}. Available: {$stock}, Required: {$item['quantity']}"
                ], 400);
            }
        }

        // Find or create project warehouse
        $projectWarehouse = Warehouse::firstOrCreate(
            ['project_id' => $project->id],
            [
                'name' => $project->name . ' Warehouse',
                'code' => 'PW-' . $project->id,
                'is_active' => true,
            ]
        );

        // Create transfer
        $transfer = StockTransfer::create([
            'from_warehouse_id' => $warehouse->id,
            'to_warehouse_id' => $projectWarehouse->id,
            'date' => $request->date,
            'note' => $request->note . ' (Transfer to project: ' . $project->name . ')',
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        foreach ($request->items as $item) {
            $transfer->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        // Auto-complete the transfer
        $transfer->complete($request->user()->id);

        return response()->json([
            'message' => 'Stock transferred to project ' . $project->name . ' successfully',
            'transfer' => $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']),
        ], 201);
    }
}
