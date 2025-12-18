<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\DamageController;
use App\Http\Controllers\Api\ProductionController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\DayTermInvestmentController;
use App\Http\Controllers\Api\AccountsReceivableController;
use App\Http\Controllers\Api\AccountsPayableController;
use App\Http\Controllers\Api\InvestLoanLiabilityController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);

    // Users (Admin only)
    Route::apiResource('users', UserController::class);
    Route::post('/users/{user}/assign-projects', [UserController::class, 'assignProjects']);
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

    // Projects
    Route::apiResource('projects', ProjectController::class);
    Route::get('/projects/{project}/summary', [ProjectController::class, 'summary']);
    Route::post('/projects/{project}/assign-users', [ProjectController::class, 'assignUsers']);
    Route::post('/projects/{project}/toggle-status', [ProjectController::class, 'toggleStatus']);
    Route::post('/projects/{project}/harvest', [ProjectController::class, 'addHarvest']);
    Route::get('/projects/{project}/harvests', [ProjectController::class, 'harvests']);
    Route::post('/projects/{project}/close', [ProjectController::class, 'closeProject']);
    Route::get('/projects/{project}/closure', [ProjectController::class, 'closureDetails']);

    // Suppliers
    Route::apiResource('suppliers', SupplierController::class);
    Route::get('/suppliers/{supplier}/ledger', [SupplierController::class, 'ledger']);
    Route::post('/suppliers/{supplier}/payment', [SupplierController::class, 'addPayment']);
    Route::get('/suppliers/{supplier}/payments', [SupplierController::class, 'payments']);
    Route::delete('/supplier-payments/{payment}', [SupplierController::class, 'deletePayment']);

    // Customers
    Route::apiResource('customers', CustomerController::class);
    Route::get('/customers/{customer}/ledger', [CustomerController::class, 'ledger']);
    Route::post('/customers/{customer}/payment', [CustomerController::class, 'addPayment']);
    Route::get('/customers/{customer}/payments', [CustomerController::class, 'payments']);
    Route::delete('/customer-payments/{payment}', [CustomerController::class, 'deletePayment']);

    // Products & Categories
    Route::apiResource('products', ProductController::class);
    Route::get('/products-low-stock', [ProductController::class, 'lowStock']);
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::post('/categories', [ProductController::class, 'storeCategory']);
    Route::put('/categories/{category}', [ProductController::class, 'updateCategory']);
    Route::delete('/categories/{category}', [ProductController::class, 'destroyCategory']);

    // Expenses
    Route::apiResource('expenses', ExpenseController::class);
    Route::get('/expense-categories', [ExpenseController::class, 'categories']);
    Route::post('/expense-categories', [ExpenseController::class, 'storeCategory']);

    // Purchases
    Route::apiResource('purchases', PurchaseController::class);
    Route::post('/purchases/{purchase}/payment', [PurchaseController::class, 'addPayment']);

    // Sales
    Route::apiResource('sales', SaleController::class);
    Route::post('/sales/{sale}/payment', [SaleController::class, 'addPayment']);

    // Assets
    Route::apiResource('assets', AssetController::class);

    // Damages
    Route::apiResource('damages', DamageController::class);

    // Productions
    Route::apiResource('productions', ProductionController::class);

    // Employees, Salaries & Advances
    Route::apiResource('employees', EmployeeController::class);
    Route::post('/employees/{employee}/salary', [EmployeeController::class, 'paySalary']);
    Route::post('/employees/{employee}/advance', [EmployeeController::class, 'giveAdvance']);
    Route::get('/employees/{employee}/salaries', [EmployeeController::class, 'employeeSalaries']);
    Route::get('/employees/{employee}/advances', [EmployeeController::class, 'employeeAdvances']);
    Route::get('/salaries', [EmployeeController::class, 'salaries']);
    Route::post('/salaries', [EmployeeController::class, 'storeSalary']);
    Route::put('/salaries/{salary}', [EmployeeController::class, 'updateSalary']);
    Route::delete('/salaries/{salary}', [EmployeeController::class, 'deleteSalary']);
    Route::get('/advances', [EmployeeController::class, 'advances']);
    Route::post('/advances', [EmployeeController::class, 'storeAdvance']);
    Route::put('/advances/{advance}', [EmployeeController::class, 'updateAdvance']);
    Route::delete('/advances/{advance}', [EmployeeController::class, 'deleteAdvance']);

    // Reports
    Route::get('/dashboard', [ReportController::class, 'dashboard']);
    Route::get('/reports/monthly', [ReportController::class, 'monthlyReport']);
    Route::get('/reports/custom', [ReportController::class, 'customReport']);
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
    Route::get('/reports/customer/{customer}', [ReportController::class, 'customerReport']);
    Route::get('/reports/product/{product}', [ReportController::class, 'productReport']);
    Route::get('/reports/project/{project}', [ReportController::class, 'projectReport']);

    // Warehouses
    Route::apiResource('warehouses', WarehouseController::class);
    Route::post('/warehouses/{warehouse}/toggle-status', [WarehouseController::class, 'toggleStatus']);
    Route::get('/warehouses/{warehouse}/stocks', [WarehouseController::class, 'stocks']);
    Route::post('/warehouses/{warehouse}/stocks', [WarehouseController::class, 'updateStock']);
    Route::get('/warehouses/{warehouse}/low-stock', [WarehouseController::class, 'lowStock']);
    Route::get('/warehouse-summary', [WarehouseController::class, 'summary']);

    // Stock Transfers
    Route::get('/stock-transfers', [WarehouseController::class, 'transfers']);
    Route::post('/stock-transfers', [WarehouseController::class, 'createTransfer']);
    Route::get('/stock-transfers/{transfer}', [WarehouseController::class, 'showTransfer']);
    Route::post('/stock-transfers/{transfer}/complete', [WarehouseController::class, 'completeTransfer']);
    Route::post('/stock-transfers/{transfer}/cancel', [WarehouseController::class, 'cancelTransfer']);
    Route::post('/stock-transfers/to-project', [WarehouseController::class, 'transferToProject']);

    // Partners & Shareholders
    Route::apiResource('partners', PartnerController::class);
    Route::post('/partners/{partner}/transaction', [PartnerController::class, 'addTransaction']);
    Route::get('/investment-transactions', [PartnerController::class, 'transactions']);

    // Loans
    Route::apiResource('loans', LoanController::class);
    Route::post('/loans/{loan}/payment', [LoanController::class, 'addPayment']);

    // Day Term Investments
    Route::apiResource('day-term-investments', DayTermInvestmentController::class);
    Route::post('/day-term-investments/{dayTermInvestment}/payment', [DayTermInvestmentController::class, 'addPayment']);

    // Accounts Receivable
    Route::apiResource('accounts-receivable', AccountsReceivableController::class);
    Route::post('/accounts-receivable/{id}/payment', [AccountsReceivableController::class, 'addPayment']);
    Route::get('/accounts-receivable/{id}/payments', [AccountsReceivableController::class, 'getPayments']);
    Route::delete('/receivable-payments/{paymentId}', [AccountsReceivableController::class, 'deletePayment']);
    Route::get('/customers/{customerId}/outstanding', [AccountsReceivableController::class, 'customerOutstanding']);
    Route::get('/receivable-report/outstanding', [AccountsReceivableController::class, 'outstandingReport']);

    // Accounts Payable
    Route::apiResource('accounts-payable', AccountsPayableController::class);
    Route::post('/accounts-payable/{id}/payment', [AccountsPayableController::class, 'addPayment']);
    Route::get('/accounts-payable/{id}/payments', [AccountsPayableController::class, 'getPayments']);
    Route::delete('/payable-payments/{paymentId}', [AccountsPayableController::class, 'deletePayment']);
    Route::get('/suppliers/{supplierId}/outstanding', [AccountsPayableController::class, 'supplierOutstanding']);
    Route::get('/payable-report/outstanding', [AccountsPayableController::class, 'outstandingReport']);

    // Invest, Loan & Liability
    Route::apiResource('invest-loan-liabilities', InvestLoanLiabilityController::class);
    Route::get('/invest-loan-liabilities-summary', [InvestLoanLiabilityController::class, 'summary']);
});
