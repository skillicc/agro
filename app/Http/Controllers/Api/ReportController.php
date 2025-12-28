<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Damage;
use App\Models\Production;
use App\Models\Salary;
use App\Models\Advance;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Asset;
use App\Models\ExpenseCategory;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $projectIds = Project::pluck('id');
        } else {
            $projectIds = $user->projects()->pluck('project_id');
        }

        $today = today();
        $thisMonth = $today->format('Y-m');
        $startOfMonth = $today->startOfMonth()->format('Y-m-d');
        $endOfMonth = $today->endOfMonth()->format('Y-m-d');

        // Calculate monthly profit
        $monthlySales = Sale::whereIn('project_id', $projectIds)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('total');
        $monthlyPurchases = Purchase::whereIn('project_id', $projectIds)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('total');
        $monthlyExpenses = Expense::whereIn('project_id', $projectIds)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        $monthlySalaries = Salary::whereIn('project_id', $projectIds)
            ->where('month', $thisMonth)
            ->sum('amount');
        $monthlyAdvances = Advance::whereIn('project_id', $projectIds)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyProfit = $monthlySales - $monthlyPurchases - $monthlyExpenses - $monthlySalaries;

        // Get employee count
        $totalEmployees = \App\Models\Employee::whereIn('project_id', $projectIds)->count();
        $activeEmployees = \App\Models\Employee::whereIn('project_id', $projectIds)->where('is_active', true)->count();

        // Get stock value
        $totalStockValue = Product::sum(DB::raw('stock_quantity * COALESCE(buying_price, 0)'));

        // Get investment/loan summary
        $totalInvestment = \App\Models\InvestLoanLiability::whereIn('type', ['partner', 'shareholder', 'investment_day_term'])->sum('amount');
        $totalLoan = \App\Models\InvestLoanLiability::where('type', 'loan')->sum('amount');

        // Get top selling products this month
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereIn('sales.project_id', $projectIds)
            ->whereBetween('sales.date', [$startOfMonth, $endOfMonth])
            ->select('products.name', DB::raw('SUM(sale_items.quantity) as total_qty'), DB::raw('SUM(sale_items.subtotal) as total_amount'))
            ->groupBy('sale_items.product_id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return response()->json([
            'total_projects' => Project::whereIn('id', $projectIds)->count(),
            'total_suppliers' => Supplier::count(),
            'total_customers' => Customer::count(),
            'total_products' => Product::count(),
            'low_stock_products' => Product::whereRaw('stock_quantity <= alert_quantity')->count(),

            'monthly_expenses' => $monthlyExpenses,
            'monthly_purchases' => $monthlyPurchases,
            'monthly_sales' => $monthlySales,
            'monthly_salaries' => $monthlySalaries,
            'monthly_advances' => $monthlyAdvances,
            'monthly_profit' => $monthlyProfit,

            'total_employees' => $totalEmployees,
            'active_employees' => $activeEmployees,
            'total_stock_value' => $totalStockValue,

            'total_investment' => $totalInvestment,
            'total_loan' => $totalLoan,

            'total_supplier_due' => Supplier::sum('total_due'),
            'total_customer_due' => Customer::sum('total_due'),

            'recent_sales' => Sale::whereIn('project_id', $projectIds)
                ->with(['project', 'customer'])
                ->orderBy('date', 'desc')
                ->limit(5)
                ->get(),

            'recent_purchases' => Purchase::whereIn('project_id', $projectIds)
                ->with(['project', 'supplier'])
                ->orderBy('date', 'desc')
                ->limit(5)
                ->get(),

            'recent_expenses' => Expense::whereIn('project_id', $projectIds)
                ->with(['project', 'category'])
                ->orderBy('date', 'desc')
                ->limit(5)
                ->get(),

            'top_products' => $topProducts,
        ]);
    }

    public function monthlyReport(Request $request)
    {
        $request->validate([
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $month = $request->month;
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = fn($model) => $model::query();

        if ($request->project_id) {
            $query = fn($model) => $model::where('project_id', $request->project_id);
        }

        return response()->json([
            'month' => $month,
            'expenses' => $query(Expense::class)
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['category', 'project'])
                ->get()
                ->groupBy('expense_category_id')
                ->map(fn($items) => [
                    'category' => $items->first()->category->name ?? 'Unknown',
                    'total' => $items->sum('amount'),
                    'count' => $items->count(),
                ]),

            'total_expenses' => $query(Expense::class)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount'),

            'total_purchases' => $query(Purchase::class)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('total'),

            'total_sales' => $query(Sale::class)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('total'),

            'total_damages' => $query(Damage::class)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('value'),

            'total_productions' => $query(Production::class)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('quantity'),

            'total_salaries' => Salary::where('month', $month)
                ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
                ->sum('amount'),

            'total_advances' => $query(Advance::class)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount'),
        ]);
    }

    public function customReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $query = fn($model) => $model::query();

        if ($request->project_id) {
            $query = fn($model) => $model::where('project_id', $request->project_id);
        }

        return response()->json([
            'period' => ['start' => $startDate, 'end' => $endDate],

            'expenses' => $query(Expense::class)
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['category', 'project', 'creator'])
                ->orderBy('date', 'desc')
                ->get(),

            'purchases' => $query(Purchase::class)
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['supplier', 'project', 'items.product'])
                ->orderBy('date', 'desc')
                ->get(),

            'sales' => $query(Sale::class)
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['customer', 'project', 'items.product'])
                ->orderBy('date', 'desc')
                ->get(),

            'damages' => $query(Damage::class)
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['product', 'project'])
                ->orderBy('date', 'desc')
                ->get(),

            'productions' => $query(Production::class)
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['product', 'project'])
                ->orderBy('date', 'desc')
                ->get(),

            'summary' => [
                'total_expenses' => $query(Expense::class)->whereBetween('date', [$startDate, $endDate])->sum('amount'),
                'total_purchases' => $query(Purchase::class)->whereBetween('date', [$startDate, $endDate])->sum('total'),
                'total_sales' => $query(Sale::class)->whereBetween('date', [$startDate, $endDate])->sum('total'),
                'total_damages' => $query(Damage::class)->whereBetween('date', [$startDate, $endDate])->sum('value'),
                'gross_profit' => $query(Sale::class)->whereBetween('date', [$startDate, $endDate])->sum('total')
                    - $query(Purchase::class)->whereBetween('date', [$startDate, $endDate])->sum('total'),
            ],
        ]);
    }

    public function customerReport(Request $request, Customer $customer)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = $customer->sales();

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        return response()->json([
            'customer' => $customer,
            'sales' => $query->with(['project', 'items.product'])->orderBy('date', 'desc')->get(),
            'payments' => $customer->payments()->orderBy('date', 'desc')->get(),
            'total_sale' => $customer->total_sale,
            'total_paid' => $customer->total_paid,
            'total_due' => $customer->total_due,
        ]);
    }

    public function productReport(Request $request, Product $product)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $dateFilter = function ($query) use ($request) {
            if ($request->start_date && $request->end_date) {
                $query->whereHas('sale', fn($q) => $q->whereBetween('date', [$request->start_date, $request->end_date]));
            }
        };

        $purchaseDateFilter = function ($query) use ($request) {
            if ($request->start_date && $request->end_date) {
                $query->whereHas('purchase', fn($q) => $q->whereBetween('date', [$request->start_date, $request->end_date]));
            }
        };

        return response()->json([
            'product' => $product->load('category'),
            'current_stock' => $product->stock_quantity,
            'total_purchased' => $product->purchaseItems()->when($request->start_date, $purchaseDateFilter)->sum('quantity'),
            'total_sold' => $product->saleItems()->when($request->start_date, $dateFilter)->sum('quantity'),
            'total_damaged' => $product->damages()
                ->when($request->start_date && $request->end_date, fn($q) => $q->whereBetween('date', [$request->start_date, $request->end_date]))
                ->sum('quantity'),
            'total_produced' => $product->productions()
                ->when($request->start_date && $request->end_date, fn($q) => $q->whereBetween('date', [$request->start_date, $request->end_date]))
                ->sum('quantity'),
        ]);
    }

    public function projectReport(Request $request, Project $project)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $dateFilter = function ($query) use ($request) {
            if ($request->start_date && $request->end_date) {
                $query->whereBetween('date', [$request->start_date, $request->end_date]);
            }
        };

        return response()->json([
            'project' => $project,
            'total_expenses' => $project->expenses()->when($request->start_date, $dateFilter)->sum('amount'),
            'total_purchases' => $project->purchases()->when($request->start_date, $dateFilter)->sum('total'),
            'total_sales' => $project->sales()->when($request->start_date, $dateFilter)->sum('total'),
            'total_damages' => $project->damages()->when($request->start_date, $dateFilter)->sum('value'),
            'total_assets' => $project->assets()->sum('value'),
            'total_salaries' => $project->salaries()
                ->when($request->start_date && $request->end_date, fn($q) => $q->whereBetween('payment_date', [$request->start_date, $request->end_date]))
                ->sum('amount'),
            'expense_breakdown' => $project->expenses()
                ->when($request->start_date, $dateFilter)
                ->with('category')
                ->get()
                ->groupBy('expense_category_id')
                ->map(fn($items) => [
                    'category' => $items->first()->category->name ?? 'Unknown',
                    'total' => $items->sum('amount'),
                ]),
        ]);
    }

    public function profitLoss(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $startDate = $request->start_date ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::now()->format('Y-m-d');

        $query = fn($model) => $model::query();

        if ($request->project_id) {
            $query = fn($model) => $model::where('project_id', $request->project_id);
        }

        // Get Courier & Transportation category IDs
        $courierTransportCategoryIds = ExpenseCategory::whereIn('name', ['Courier', 'Transportation'])->pluck('id');

        // Revenue
        $totalSales = $query(Sale::class)->whereBetween('date', [$startDate, $endDate])->sum('total');

        // Expenses
        $totalPurchases = $query(Purchase::class)->whereBetween('date', [$startDate, $endDate])->sum('total');

        $totalExpenses = $query(Expense::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotIn('expense_category_id', $courierTransportCategoryIds)
            ->sum('amount');

        $courierTransport = $query(Expense::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('expense_category_id', $courierTransportCategoryIds)
            ->sum('amount');

        $totalSalaries = Salary::when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        $totalDamages = $query(Damage::class)->whereBetween('date', [$startDate, $endDate])->sum('value');

        // Asset Depreciation Calculation
        $assetQuery = Asset::query();
        if ($request->project_id) {
            $assetQuery->where('project_id', $request->project_id);
        }

        $assets = $assetQuery->get()->map(function ($asset) use ($startDate, $endDate) {
            // Calculate depreciation based on the report period
            $purchaseDate = Carbon::parse($asset->purchase_date ?? $asset->created_at);
            $reportStart = Carbon::parse($startDate);
            $reportEnd = Carbon::parse($endDate);

            // Calculate years since purchase
            $yearsSincePurchase = $purchaseDate->diffInDays($reportEnd) / 365;

            // Calculate depreciation for the period
            $annualDepreciation = ($asset->value * ($asset->depreciation_rate ?? 0)) / 100;
            $periodDays = $reportStart->diffInDays($reportEnd);
            $periodDepreciation = ($annualDepreciation / 365) * $periodDays;

            // Calculate current value
            $totalDepreciation = $annualDepreciation * $yearsSincePurchase;
            $currentValue = max(0, $asset->value - $totalDepreciation);

            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'value' => $asset->value,
                'depreciation_rate' => $asset->depreciation_rate ?? 0,
                'depreciation_amount' => round($periodDepreciation, 2),
                'current_value' => round($currentValue, 2),
            ];
        });

        $totalDepreciation = $assets->sum('depreciation_amount');
        $totalAssetCurrentValue = $assets->sum('current_value');

        // Profit Calculations
        $grossProfit = $totalSales - $totalPurchases;
        $operatingProfit = $grossProfit - $totalExpenses - $courierTransport;
        $netProfit = $operatingProfit - $totalSalaries - $totalDamages - $totalDepreciation;

        // Expense Breakdown
        $expenseBreakdown = $query(Expense::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category')
            ->get()
            ->groupBy('expense_category_id')
            ->map(fn($items) => [
                'category' => $items->first()->category->name ?? 'Unknown',
                'total' => $items->sum('amount'),
            ])
            ->values();

        return response()->json([
            'period' => ['start' => $startDate, 'end' => $endDate],

            'revenue' => [
                'sales' => $totalSales,
                'other_income' => 0, // Can be extended later
                'total' => $totalSales,
            ],

            'expenses' => [
                'purchases' => $totalPurchases,
                'operating' => $totalExpenses,
                'salaries' => $totalSalaries,
                'courier_transport' => $courierTransport,
                'damages' => $totalDamages,
                'depreciation' => $totalDepreciation,
                'total' => $totalPurchases + $totalExpenses + $totalSalaries + $courierTransport + $totalDamages + $totalDepreciation,
            ],

            'assets' => $assets,
            'total_asset_current_value' => $totalAssetCurrentValue,

            'gross_profit' => $grossProfit,
            'operating_profit' => $operatingProfit,
            'net_profit' => $netProfit,

            'expense_breakdown' => $expenseBreakdown,
        ]);
    }

    public function warehouseReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $warehouseQuery = Warehouse::query();
        if ($request->warehouse_id) {
            $warehouseQuery->where('id', $request->warehouse_id);
        }

        $warehouses = $warehouseQuery->withCount('stocks')->get()->map(function ($warehouse) use ($startDate, $endDate) {
            // Calculate stock value
            $stockValue = $warehouse->stocks()
                ->join('products', 'warehouse_stocks.product_id', '=', 'products.id')
                ->sum(DB::raw('warehouse_stocks.quantity * COALESCE(products.buying_price, 0)'));

            // Calculate expenses
            $expenseQuery = $warehouse->expenses();
            if ($startDate && $endDate) {
                $expenseQuery->whereBetween('date', [$startDate, $endDate]);
            }
            $totalExpenses = $expenseQuery->sum('amount');

            // Calculate transfers
            $transfersInQuery = StockTransfer::where('to_warehouse_id', $warehouse->id);
            $transfersOutQuery = StockTransfer::where('from_warehouse_id', $warehouse->id);

            if ($startDate && $endDate) {
                $transfersInQuery->whereBetween('date', [$startDate, $endDate]);
                $transfersOutQuery->whereBetween('date', [$startDate, $endDate]);
            }

            return [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'location' => $warehouse->address,
                'stocks_count' => $warehouse->stocks_count,
                'stock_value' => $stockValue,
                'total_expenses' => $totalExpenses,
                'transfers_in' => $transfersInQuery->count(),
                'transfers_out' => $transfersOutQuery->count(),
            ];
        });

        // Summary
        $totalStockValue = $warehouses->sum('stock_value');
        $totalExpenses = $warehouses->sum('total_expenses');
        $totalTransfersIn = $warehouses->sum('transfers_in');
        $totalTransfersOut = $warehouses->sum('transfers_out');
        $totalStockItems = $warehouses->sum('stocks_count');

        // Expense breakdown by category
        $expenseQuery = Expense::whereNotNull('warehouse_id');
        if ($request->warehouse_id) {
            $expenseQuery->where('warehouse_id', $request->warehouse_id);
        }
        if ($startDate && $endDate) {
            $expenseQuery->whereBetween('date', [$startDate, $endDate]);
        }

        $expenseBreakdown = $expenseQuery
            ->with('category')
            ->get()
            ->groupBy('expense_category_id')
            ->map(fn($items) => [
                'category' => $items->first()->category->name ?? 'Unknown',
                'total' => $items->sum('amount'),
                'count' => $items->count(),
            ])
            ->values();

        return response()->json([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'summary' => [
                'total_warehouses' => $warehouses->count(),
                'total_stock_value' => $totalStockValue,
                'total_expenses' => $totalExpenses,
                'total_transfers_in' => $totalTransfersIn,
                'total_transfers_out' => $totalTransfersOut,
                'total_stock_items' => $totalStockItems,
            ],
            'warehouses' => $warehouses,
            'expense_breakdown' => $expenseBreakdown,
        ]);
    }
}
