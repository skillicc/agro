<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Salary;
use App\Models\Advance;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('project');

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $employees = $query->orderBy('name')->get();

        // Calculate total paid and current month due for each employee
        $currentMonth = now()->format('Y-m');

        $employees->each(function ($employee) use ($currentMonth) {
            $totalSalaryPaid = $employee->salaries()->sum('amount');
            $totalAdvancePaid = $employee->advances()->sum('amount');
            $employee->total_paid = $totalSalaryPaid + $totalAdvancePaid;

            // Check if current month salary is paid
            $currentMonthSalaryPaid = $employee->salaries()
                ->where('month', $currentMonth)
                ->sum('amount');

            // Current month due = monthly salary - what's paid this month
            $employee->current_month_due = max(0, $employee->salary_amount - $currentMonthSalaryPaid);
            $employee->current_month_paid = $currentMonthSalaryPaid;

            // Also store individual totals
            $employee->total_salary_paid = $totalSalaryPaid;
            $employee->total_advance_paid = $totalAdvancePaid;
        });

        return response()->json($employees);
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'salary_amount' => 'required|numeric|min:0',
            'joining_date' => 'nullable|date',
        ]);

        $employee = Employee::create($request->all());

        return response()->json($employee->load('project'), 201);
    }

    public function show(Employee $employee)
    {
        $employee->load(['project', 'salaries', 'advances']);
        return response()->json($employee);
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'salary_amount' => 'required|numeric|min:0',
            'joining_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $employee->update($request->all());

        return response()->json($employee->load('project'));
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return response()->json(['message' => 'Employee deleted successfully']);
    }

    public function paySalary(Request $request, Employee $employee)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'payment_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $salary = Salary::create([
            'project_id' => $employee->project_id,
            'employee_id' => $employee->id,
            'amount' => $request->amount,
            'month' => $request->month,
            'payment_date' => $request->payment_date,
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($salary->load(['employee', 'creator']), 201);
    }

    public function giveAdvance(Request $request, Employee $employee)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $advance = Advance::create([
            'project_id' => $employee->project_id,
            'employee_id' => $employee->id,
            'amount' => $request->amount,
            'date' => $request->date,
            'reason' => $request->reason,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($advance->load(['employee', 'creator']), 201);
    }

    public function salaries(Request $request)
    {
        $query = Salary::with(['project', 'employee', 'creator']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->month) {
            $query->where('month', $request->month);
        }

        return response()->json($query->orderBy('payment_date', 'desc')->get());
    }

    public function advances(Request $request)
    {
        $query = Advance::with(['project', 'employee', 'creator']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        return response()->json($query->orderBy('date', 'desc')->get());
    }

    public function employeeSalaries(Employee $employee)
    {
        $salaries = $employee->salaries()->with('creator')->orderBy('payment_date', 'desc')->get();
        return response()->json($salaries);
    }

    public function employeeAdvances(Employee $employee)
    {
        $advances = $employee->advances()->with('creator')->orderBy('date', 'desc')->get();
        return response()->json($advances);
    }

    public function storeSalary(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'payment_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        $salary = Salary::create([
            'project_id' => $request->project_id ?? $employee->project_id,
            'employee_id' => $request->employee_id,
            'amount' => $request->amount,
            'month' => $request->month,
            'payment_date' => $request->payment_date,
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($salary->load(['employee', 'creator']), 201);
    }

    public function storeAdvance(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        $advance = Advance::create([
            'project_id' => $request->project_id ?? $employee->project_id,
            'employee_id' => $request->employee_id,
            'amount' => $request->amount,
            'date' => $request->date,
            'reason' => $request->reason,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($advance->load(['employee', 'creator']), 201);
    }

    public function updateSalary(Request $request, Salary $salary)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'payment_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $salary->update([
            'amount' => $request->amount,
            'month' => $request->month,
            'payment_date' => $request->payment_date,
            'note' => $request->note,
        ]);

        return response()->json($salary->load(['employee', 'creator']));
    }

    public function deleteSalary(Salary $salary)
    {
        $salary->delete();
        return response()->json(['message' => 'Salary deleted successfully']);
    }

    public function updateAdvance(Request $request, Advance $advance)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reason' => 'nullable|string',
            'is_deducted' => 'nullable|boolean',
        ]);

        $advance->update([
            'amount' => $request->amount,
            'date' => $request->date,
            'reason' => $request->reason,
            'is_deducted' => $request->is_deducted ?? false,
        ]);

        return response()->json($advance->load(['employee', 'creator']));
    }

    public function deleteAdvance(Advance $advance)
    {
        $advance->delete();
        return response()->json(['message' => 'Advance deleted successfully']);
    }
}
