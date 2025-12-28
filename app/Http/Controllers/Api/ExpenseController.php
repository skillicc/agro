<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Project;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['project', 'warehouse', 'category', 'creator']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->category_id) {
            $query->where('expense_category_id', $request->category_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $expenses = $query->orderBy('date', 'desc')->get();

        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $rules = [
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'bill_no' => 'nullable|string',
        ];

        // Either project_id or warehouse_id is required
        if ($request->warehouse_id) {
            $rules['warehouse_id'] = 'required|exists:warehouses,id';
            $rules['project_id'] = 'nullable|exists:projects,id';
        } else {
            $rules['project_id'] = 'required|exists:projects,id';
            $rules['warehouse_id'] = 'nullable|exists:warehouses,id';
        }

        $request->validate($rules);

        $expense = Expense::create([
            ...$request->all(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($expense->load(['project', 'warehouse', 'category', 'creator']), 201);
    }

    public function show(Expense $expense)
    {
        return response()->json($expense->load(['project', 'category', 'creator']));
    }

    public function update(Request $request, Expense $expense)
    {
        $rules = [
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'bill_no' => 'nullable|string',
        ];

        // Either project_id or warehouse_id is required
        if ($request->warehouse_id || $expense->warehouse_id) {
            $rules['warehouse_id'] = 'nullable|exists:warehouses,id';
            $rules['project_id'] = 'nullable|exists:projects,id';
        } else {
            $rules['project_id'] = 'required|exists:projects,id';
        }

        $request->validate($rules);

        $expense->update($request->all());

        return response()->json($expense->load(['project', 'warehouse', 'category', 'creator']));
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return response()->json(['message' => 'Expense deleted successfully']);
    }

    public function categories(Request $request)
    {
        $query = ExpenseCategory::where('is_active', true);

        if ($request->project_type) {
            $query->forProjectType($request->project_type);
        }

        return response()->json($query->get());
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'project_type' => 'nullable|in:field,nursery,shop,warehouse,all',
        ]);

        $data = $request->all();
        if (!isset($data['project_type'])) {
            $data['project_type'] = 'all';
        }

        $category = ExpenseCategory::create($data);

        return response()->json($category, 201);
    }
}
