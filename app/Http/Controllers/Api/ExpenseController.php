<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Land;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['project', 'warehouse', 'land', 'category', 'creator', 'editor']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->land_id) {
            $query->where('land_id', $request->land_id);
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
            'land_id' => 'nullable|exists:lands,id',
        ];

        if ($request->warehouse_id) {
            $rules['warehouse_id'] = 'required|exists:warehouses,id';
            $rules['project_id'] = 'nullable|exists:projects,id';
        } else {
            $rules['project_id'] = 'required|exists:projects,id';
            $rules['warehouse_id'] = 'nullable|exists:warehouses,id';
        }

        $data = $request->validate($rules);

        if (!empty($data['warehouse_id'])) {
            $data['land_id'] = null;
        }

        if (!empty($data['land_id'])) {
            $this->ensureLandBelongsToProject($data['land_id'], $data['project_id'] ?? null);
        }

        $expense = DB::transaction(function () use ($data, $request) {
            $expense = Expense::create([
                ...$data,
                'created_by' => $request->user()->id,
            ]);

            $this->recordHistory(
                $expense,
                'created',
                $request->user()->id,
                [],
                $this->extractHistorySnapshot($expense)
            );

            return $expense;
        });

        return response()->json($this->loadExpenseRelations($expense), 201);
    }

    public function show(Expense $expense)
    {
        return response()->json($this->loadExpenseRelations($expense));
    }

    public function history(Expense $expense)
    {
        return response()->json(
            $expense->history()
                ->with('user:id,name')
                ->get()
        );
    }

    public function update(Request $request, Expense $expense)
    {
        $rules = [
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'bill_no' => 'nullable|string',
            'land_id' => 'nullable|exists:lands,id',
        ];

        if ($request->warehouse_id || $expense->warehouse_id) {
            $rules['warehouse_id'] = 'nullable|exists:warehouses,id';
            $rules['project_id'] = 'nullable|exists:projects,id';
        } else {
            $rules['project_id'] = 'required|exists:projects,id';
        }

        $data = $request->validate($rules);

        if (!empty($data['warehouse_id'])) {
            $data['land_id'] = null;
        }

        if (!empty($data['land_id'])) {
            $projectId = $data['project_id'] ?? $expense->project_id;
            $this->ensureLandBelongsToProject($data['land_id'], $projectId);
        }

        $before = $this->extractHistorySnapshot($expense);

        DB::transaction(function () use ($expense, $data, $request, $before) {
            $expense->update([
                ...$data,
                'updated_by' => $request->user()->id,
            ]);

            $this->recordHistory(
                $expense->fresh(),
                'updated',
                $request->user()->id,
                $before,
                $this->extractHistorySnapshot($expense->fresh())
            );
        });

        return response()->json($this->loadExpenseRelations($expense->fresh()));
    }

    public function destroy(Request $request, Expense $expense)
    {
        DB::transaction(function () use ($expense, $request) {
            $this->recordHistory(
                $expense,
                'deleted',
                $request->user()?->id,
                $this->extractHistorySnapshot($expense),
                []
            );

            $expense->delete();
        });

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

    private function loadExpenseRelations(Expense $expense): Expense
    {
        return $expense->load(['project', 'warehouse', 'land', 'category', 'creator', 'editor']);
    }

    private function extractHistorySnapshot(Expense $expense): array
    {
        return [
            'project_id' => $expense->project_id,
            'land_id' => $expense->land_id,
            'warehouse_id' => $expense->warehouse_id,
            'expense_category_id' => $expense->expense_category_id,
            'bill_no' => $expense->bill_no,
            'amount' => (float) ($expense->amount ?? 0),
            'date' => $expense->date?->format('Y-m-d') ?? $expense->date,
            'description' => $expense->description,
        ];
    }

    private function recordHistory(Expense $expense, string $action, ?int $userId, array $oldValues, array $newValues): void
    {
        $changes = $this->buildHistoryChanges($oldValues, $newValues);

        if ($action === 'updated' && empty($changes)) {
            return;
        }

        $expense->history()->create([
            'action' => $action,
            'changed_by' => $userId,
            'changes' => $changes,
        ]);
    }

    private function buildHistoryChanges(array $oldValues, array $newValues): array
    {
        $fieldLabels = [
            'project_id' => 'Project',
            'land_id' => 'Land',
            'warehouse_id' => 'Warehouse',
            'expense_category_id' => 'Category',
            'bill_no' => 'Bill No.',
            'amount' => 'Amount',
            'date' => 'Date',
            'description' => 'Description',
        ];

        $changes = [];

        foreach ($fieldLabels as $field => $label) {
            $oldValue = $oldValues[$field] ?? null;
            $newValue = $newValues[$field] ?? null;

            if (empty($oldValues) || $oldValue !== $newValue) {
                $changes[$field] = [
                    'label' => $label,
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    private function ensureLandBelongsToProject(int|string $landId, int|string|null $projectId): void
    {
        if (!$projectId) {
            throw ValidationException::withMessages([
                'land_id' => 'A project must be selected before assigning a land.',
            ]);
        }

        $belongsToProject = Land::whereKey($landId)
            ->whereHas('projects', function ($query) use ($projectId) {
                $query->where('projects.id', $projectId);
            })
            ->exists();

        if (!$belongsToProject) {
            throw ValidationException::withMessages([
                'land_id' => 'The selected land is not assigned to this project.',
            ]);
        }
    }
}
