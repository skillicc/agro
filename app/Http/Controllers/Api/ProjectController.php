<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Harvest;
use App\Models\ProjectClosure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $projects = Project::withCount(['expenses', 'purchases', 'sales'])->get();
        } else {
            $projects = $user->projects()->withCount(['expenses', 'purchases', 'sales'])->get();
        }

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:field,nursery,shop,administration,central',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'expected_harvest_date' => 'nullable|date',
            'duration_months' => 'nullable|integer|min:1|max:12',
        ]);

        $project = Project::create($request->all());

        return response()->json($project, 201);
    }

    public function show(Request $request, Project $project)
    {
        $user = $request->user();

        if (!$user->hasProjectAccess($project)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $project->loadCount(['expenses', 'purchases', 'sales', 'damages', 'productions']);
        $project->load('users');

        return response()->json($project);
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:field,nursery,shop,administration,central',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'expected_harvest_date' => 'nullable|date',
            'actual_harvest_date' => 'nullable|date',
            'duration_months' => 'nullable|integer|min:1|max:12',
            'project_status' => 'nullable|in:planning,active,harvesting,completed,closed',
        ]);

        $project->update($request->all());

        return response()->json($project);
    }

    public function destroy(Request $request, Project $project)
    {
        $request->validate([
            'admin_password' => 'required|string',
        ]);

        // Get an admin user to verify password
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

    // Add harvest record
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

        // Update project status to harvesting if not already
        if ($project->project_status === 'active') {
            $project->update(['project_status' => 'harvesting']);
        }

        return response()->json($harvest->load(['project', 'product', 'creator']), 201);
    }

    // Get project harvests
    public function harvests(Project $project)
    {
        $harvests = $project->harvests()->with(['product', 'creator'])->orderBy('harvest_date', 'desc')->get();
        return response()->json($harvests);
    }

    // Close project and calculate final profit/loss
    public function closeProject(Request $request, Project $project)
    {
        $request->validate([
            'closure_date' => 'required|date',
            'other_income' => 'nullable|numeric|min:0',
            'partner_shares' => 'nullable|array',
            'summary' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        // Check if already closed
        if ($project->is_closed) {
            return response()->json(['message' => 'Project is already closed'], 400);
        }

        // Calculate totals
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

        // Create closure record
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

        // Update project status
        $project->update([
            'is_closed' => true,
            'closure_date' => $request->closure_date,
            'project_status' => 'closed',
        ]);

        return response()->json($closure->load(['project', 'closedBy']), 201);
    }

    // Get project closure details
    public function closureDetails(Project $project)
    {
        if (!$project->is_closed) {
            return response()->json(['message' => 'Project is not closed yet'], 404);
        }

        $closure = $project->closure()->with(['closedBy'])->first();
        return response()->json($closure);
    }
}
