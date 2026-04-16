<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->productsQuery();

        if ($request->filled('project_id')) {
            $projectId = $request->project_id;

            $query->where(function ($productQuery) use ($projectId) {
                $productQuery->where('project_id', $projectId)
                    ->orWhereHas('productions', function ($relationQuery) use ($projectId) {
                        $relationQuery->where('project_id', $projectId);
                    })
                    ->orWhereHas('damages', function ($relationQuery) use ($projectId) {
                        $relationQuery->where('project_id', $projectId);
                    })
                    ->orWhereHas('purchaseItems.purchase', function ($relationQuery) use ($projectId) {
                        $relationQuery->where('project_id', $projectId);
                    })
                    ->orWhereHas('saleItems.sale', function ($relationQuery) use ($projectId) {
                        $relationQuery->where('project_id', $projectId);
                    });
            });
        } elseif ($request->filled('warehouse_id')) {
            $warehouse = Warehouse::find($request->warehouse_id);
            if ($warehouse?->project_id) {
                $query->where('project_id', $warehouse->project_id);
            }
        }

        $products = $query->get();
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'category_id' => 'nullable|exists:categories,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'type' => 'required|in:own_production,trading',
            'project_type' => 'nullable|in:administration,central',
            'unit' => 'required|string|max:50',
            'buying_price' => 'nullable|numeric|min:0',
            'production_cost' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'alert_quantity' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ];

        $request->validate($rules);

        $product = Product::create($request->except('category_ids'));

        // Sync multiple categories if provided
        if ($request->has('category_ids')) {
            $product->categories()->sync($request->category_ids);
        }

        return response()->json($product->load(['category', 'categories']), 201);
    }

    public function show(Product $product)
    {
        return response()->json($this->productsQuery()->findOrFail($product->id));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'category_id' => 'nullable|exists:categories,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'type' => 'nullable|in:own_production,trading',
            'project_type' => 'nullable|in:administration,central',
            'unit' => 'nullable|string|max:50',
            'buying_price' => 'nullable|numeric|min:0',
            'production_cost' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'alert_quantity' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $product->update($request->except('category_ids'));

        // Sync multiple categories if provided
        if ($request->has('category_ids')) {
            $product->categories()->sync($request->category_ids);
        }

        return response()->json($product->load(['category', 'categories']));
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function lowStock()
    {
        $products = $this->productsQuery()
            ->get()
            ->filter(fn ($product) => $product->stock_quantity <= ($product->alert_quantity ?? 0))
            ->values();

        return response()->json($products);
    }

    public function categories()
    {
        return response()->json(Category::all());
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
        ]);

        $category = Category::create($request->all());

        return response()->json($category, 201);
    }

    public function updateCategory(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
        ]);

        $category->update($request->all());

        return response()->json($category);
    }

    public function destroyCategory(Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }

    private function productsQuery()
    {
        return Product::with(['project', 'category', 'categories'])
            ->withSum([
                'stockBatches as legacy_adjustment_quantity' => function ($query) {
                    $query->legacyMigrationAdjustments();
                },
            ], 'remaining_quantity');
    }
}
