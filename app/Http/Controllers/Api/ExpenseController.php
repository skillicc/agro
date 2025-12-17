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
        $query = Expense::with(['project', 'category', 'creator']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
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
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $expense = Expense::create([
            ...$request->all(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($expense->load(['project', 'category', 'creator']), 201);
    }

    public function show(Expense $expense)
    {
        return response()->json($expense->load(['project', 'category', 'creator']));
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $expense->update($request->all());

        return response()->json($expense->load(['project', 'category', 'creator']));
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
            'project_type' => 'required|in:field,nursery,shop,all',
        ]);

        $category = ExpenseCategory::create($request->all());

        return response()->json($category, 201);
    }
}
