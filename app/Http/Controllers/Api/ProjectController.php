<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Harvest;
use App\Models\LandCultivation;
use App\Models\Project;
use App\Models\ProjectClosure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $builder = $user->isAdmin() ? Project::query() : $user->projects();

        $projects = $builder
            ->with(['lands:id,name'])
            ->withCount(['expenses', 'purchases', 'sales', 'lands'])
            ->withSum('expenses', 'amount')
            ->withSum('assets', 'value')
            ->withSum('sales', 'total')
            ->get();

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:field,nursery,shop,administration,central',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'expected_harvest_date' => 'nullable|date',
            'duration_months' => 'nullable|integer|min:1|max:12',
            'land_ids' => 'nullable|array',
            'land_ids.*' => 'exists:lands,id',
        ]);

        $project = Project::create(Arr::except($data, ['land_ids']));
        $this->syncProjectLands($project, $data['land_ids'] ?? []);

        return response()->json($project->load('lands'), 201);
    }

    public function show(Request $request, Project $project)
    {
        $user = $request->user();

        if (!$user->hasProjectAccess($project)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $project->loadCount(['expenses', 'purchases', 'sales', 'damages', 'productions', 'lands']);
        $project->load(['users', 'lands.currentCultivation']);

        return response()->json($project);
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:field,nursery,shop,administration,central',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'expected_harvest_date' => 'nullable|date',
            'actual_harvest_date' => 'nullable|date',
            'duration_months' => 'nullable|integer|min:1|max:12',
            'project_status' => 'nullable|in:planning,active,harvesting,completed,closed',
            'land_ids' => 'nullable|array',
            'land_ids.*' => 'exists:lands,id',
        ]);

        $project->update(Arr::except($data, ['land_ids']));
        if (array_key_exists('land_ids', $data)) {
            $this->syncProjectLands($project, $data['land_ids'] ?? []);
        }

        return response()->json($project->load('lands'));
    }

    public function destroy(Request $request, Project $project)
    {
        $request->validate([
            'admin_password' => 'required|string',
        ]);

        $admin = User::where('role', 'admin')->first();

        if (!$admin || !Hash::check($request->admin_password, $admin->password)) {
            return response()->json(['message' => 'Invalid admin password'], 403);
        }

        $project->delete();
        return response()->json(['message' => 'Project deleted successfully']);
    }

    public function summary(Request $request, Project $project)
    {
        $user = $request->user();

        if (!$user->hasProjectAccess($project)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $summary = [
            'total_expenses' => $project->expenses()->sum('amount'),
            'total_purchases' => $project->purchases()->sum('total'),
            'total_sales' => $project->sales()->sum('total'),
            'total_damages' => $project->damages()->sum('value'),
            'total_salaries' => $project->salaries()->sum('amount'),
            'total_advances' => $project->advances()->sum('amount'),
            'total_assets' => $project->assets()->sum('value'),
            'expense_count' => $project->expenses()->count(),
            'purchase_count' => $project->purchases()->count(),
            'sale_count' => $project->sales()->count(),
            'employee_count' => $project->employees()->where('is_active', true)->count(),
            'land_count' => $project->lands()->count(),
            'land_expenses' => $project->expenses()->whereNotNull('land_id')->sum('amount'),
        ];

        return response()->json($summary);
    }

    public function assignUsers(Request $request, Project $project)
    {
        $request->validate([
            'users' => 'required|array',
            'users.*.user_id' => 'required|exists:users,id',
            'users.*.permission' => 'required|in:full,read_write,read_only',
        ]);

        $syncData = [];
        foreach ($request->users as $user) {
            $syncData[$user['user_id']] = ['permission' => $user['permission']];
        }

        $project->users()->sync($syncData);

        return response()->json($project->load('users'));
    }

    public function toggleStatus(Project $project)
    {
        $project->is_active = !$project->is_active;
        $project->save();

        return response()->json([
            'message' => $project->is_active ? 'Project activated' : 'Project deactivated',
            'project' => $project
        ]);
    }

    public function landLedger(Request $request, Project $project)
    {
        $user = $request->user();

        if (!$user->hasProjectAccess($project)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $lands = $project->lands()
            ->orderBy('name')
            ->get()
            ->map(function ($land) use ($project) {
                $cultivations = $land->cultivations()
                    ->where('project_id', $project->id)
                    ->orderByDesc('opening_date')
                    ->get();

                $expenses = $land->expenses()
                    ->with(['category', 'creator'])
                    ->where('project_id', $project->id)
                    ->orderByDesc('date')
                    ->get();

                $sales = $land->sales()
                    ->with(['customer'])
                    ->where('project_id', $project->id)
                    ->orderByDesc('date')
                    ->get();

                $totalExpenses = (float) $expenses->sum('amount');
                $totalSales = (float) $sales->sum(fn ($sale) => $sale->total ?? $sale->subtotal ?? 0);
                $totalPaid = (float) $sales->sum('paid');
                $totalDue = (float) $sales->sum('due');

                return [
                    'id' => $land->id,
                    'name' => $land->name,
                    'code' => $land->code,
                    'location' => $land->location,
                    'size' => $land->size,
                    'unit' => $land->unit,
                    'notes' => $land->notes,
                    'current_cultivation' => $cultivations->firstWhere('status', 'active'),
                    'cultivations' => $cultivations->values(),
                    'recent_expenses' => $expenses->take(10)->values(),
                    'recent_sales' => $sales->take(10)->values(),
                    'expense_count' => $expenses->count(),
                    'sales_count' => $sales->count(),
                    'total_expenses' => $totalExpenses,
                    'total_sales' => $totalSales,
                    'total_paid' => $totalPaid,
                    'total_due' => $totalDue,
                    'profit' => $totalSales - $totalExpenses,
                ];
            })
            ->values();

        return response()->json([
            'lands' => $lands,
            'totals' => [
                'land_count' => $lands->count(),
                'active_cultivations' => $lands->filter(fn ($land) => !empty($land['current_cultivation']))->count(),
                'total_expenses' => (float) $lands->sum('total_expenses'),
                'total_sales' => (float) $lands->sum('total_sales'),
                'total_paid' => (float) $lands->sum('total_paid'),
                'total_due' => (float) $lands->sum('total_due'),
                'total_profit' => (float) $lands->sum('profit'),
                'unassigned_expenses' => (float) $project->expenses()->whereNull('land_id')->sum('amount'),
                'unassigned_sales' => (float) $project->sales()->whereNull('land_id')->sum('total'),
            ],
        ]);
    }

    public function storeLandCultivation(Request $request, Project $project)
    {
        $user = $request->user();

        if (!$user->hasProjectAccess($project)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $data = $request->validate([
            'land_id' => 'required|exists:lands,id',
            'crop_name' => 'required|string|max:255',
            'opening_date' => 'required|date',
            'expected_closing_date' => 'nullable|date|after_or_equal:opening_date',
            'closing_date' => 'nullable|date|after_or_equal:opening_date',
            'status' => 'nullable|in:active,closed',
            'notes' => 'nullable|string',
        ]);

        if (!$project->lands()->where('lands.id', $data['land_id'])->exists()) {
            $project->lands()->syncWithoutDetaching([$data['land_id']]);
        }

        $hasActiveCycle = LandCultivation::where('land_id', $data['land_id'])
            ->where('status', 'active')
            ->exists();

        if ($hasActiveCycle) {
            return response()->json([
                'message' => 'This land already has an active crop cycle. Please close it first.'
            ], 422);
        }

        $cycle = LandCultivation::create([
            ...$data,
            'project_id' => $project->id,
            'status' => !empty($data['closing_date']) ? 'closed' : ($data['status'] ?? 'active'),
        ]);

        return response()->json($cycle->load(['land', 'project']), 201);
    }

    public function updateLandCultivation(Request $request, LandCultivation $landCultivation)
    {
        $user = $request->user();

        if (!$user->hasProjectAccess($landCultivation->project)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $data = $request->validate([
            'crop_name' => 'sometimes|string|max:255',
            'opening_date' => 'sometimes|date',
            'expected_closing_date' => 'nullable|date',
            'closing_date' => 'nullable|date',
            'status' => 'nullable|in:active,closed',
            'notes' => 'nullable|string',
        ]);

        $wantsActive = ($data['status'] ?? $landCultivation->status) === 'active';
        if ($wantsActive) {
            $hasOtherActiveCycle = LandCultivation::where('land_id', $landCultivation->land_id)
                ->where('status', 'active')
                ->whereKeyNot($landCultivation->id)
                ->exists();

            if ($hasOtherActiveCycle) {
                return response()->json([
                    'message' => 'Another active crop cycle already exists for this land.'
                ], 422);
            }
        }

        if (!empty($data['closing_date']) && !isset($data['status'])) {
            $data['status'] = 'closed';
        }

        if (($data['status'] ?? null) === 'closed' && empty($data['closing_date'])) {
            $data['closing_date'] = now()->toDateString();
        }

        $landCultivation->update($data);

        return response()->json($landCultivation->load(['land', 'project']));
    }

    public function addHarvest(Request $request, Project $project)
    {
        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string',
            'price_per_unit' => 'required|numeric|min:0',
            'harvest_date' => 'required|date',
            'quality' => 'nullable|in:excellent,good,average,poor',
            'note' => 'nullable|string',
        ]);

        $totalValue = $request->quantity * $request->price_per_unit;

        $harvest = Harvest::create([
            'project_id' => $project->id,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'unit' => $request->unit,
            'price_per_unit' => $request->price_per_unit,
            'total_value' => $totalValue,
            'harvest_date' => $request->harvest_date,
            'quality' => $request->quality ?? 'good',
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        if ($project->project_status === 'active') {
            $project->update(['project_status' => 'harvesting']);
        }

        return response()->json($harvest->load(['project', 'product', 'creator']), 201);
    }

    public function harvests(Project $project)
    {
        $harvests = $project->harvests()->with(['product', 'creator'])->orderBy('harvest_date', 'desc')->get();
        return response()->json($harvests);
    }

    public function closeProject(Request $request, Project $project)
    {
        $request->validate([
            'closure_date' => 'required|date',
            'other_income' => 'nullable|numeric|min:0',
            'partner_shares' => 'nullable|array',
            'summary' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        if ($project->is_closed) {
            return response()->json(['message' => 'Project is already closed'], 400);
        }

        $totalSales = $project->sales()->sum('total');
        $totalHarvestValue = $project->harvests()->sum('total_value');
        $otherIncome = $request->other_income ?? 0;
        $totalIncome = $totalSales + $totalHarvestValue + $otherIncome;

        $totalPurchases = $project->purchases()->sum('total');
        $totalExpenses = $project->expenses()->sum('amount');
        $totalSalaries = $project->salaries()->sum('amount');
        $totalDamages = $project->damages()->sum('value');
        $totalCost = $totalPurchases + $totalExpenses + $totalSalaries + $totalDamages;

        $grossProfit = $totalIncome - $totalPurchases;
        $netProfit = $totalIncome - $totalCost;
        $profitPercentage = $totalCost > 0 ? ($netProfit / $totalCost) * 100 : 0;

        $closure = ProjectClosure::create([
            'project_id' => $project->id,
            'closure_date' => $request->closure_date,
            'total_sales' => $totalSales,
            'total_harvest_value' => $totalHarvestValue,
            'other_income' => $otherIncome,
            'total_income' => $totalIncome,
            'total_purchases' => $totalPurchases,
            'total_expenses' => $totalExpenses,
            'total_salaries' => $totalSalaries,
            'total_damages' => $totalDamages,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'profit_percentage' => $profitPercentage,
            'partner_shares' => $request->partner_shares,
            'summary' => $request->summary,
            'remarks' => $request->remarks,
            'closed_by' => $request->user()->id,
        ]);

        $project->update([
            'is_closed' => true,
            'closure_date' => $request->closure_date,
            'project_status' => 'closed',
        ]);

        return response()->json($closure->load(['project', 'closedBy']), 201);
    }

    public function closureDetails(Project $project)
    {
        if (!$project->is_closed) {
            return response()->json(['message' => 'Project is not closed yet'], 404);
        }

        $closure = $project->closure()->with(['closedBy'])->first();
        return response()->json($closure);
    }

    private function syncProjectLands(Project $project, array $landIds = []): void
    {
        $project->lands()->sync($landIds);
    }
}
