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

        if ($request->employee_type) {
            $query->where('employee_type', $request->employee_type);
        }

        $employees = $query->orderBy('name')->get();

        // Calculate total paid and due for each employee
        // Due is for PREVIOUS month (salary for Nov is paid in Dec)
        $currentMonth = now()->format('Y-m');
        // Use subMonthNoOverflow to handle month-end dates correctly (Dec 31 -> Nov 30, not Dec 1)
        $previousMonth = now()->subMonthNoOverflow()->format('Y-m');

        $employees->each(function ($employee) use ($currentMonth, $previousMonth) {
            $totalSalaryPaid = $employee->salaries()->sum('amount');
            $totalAdvancePaid = $employee->advances()->sum('amount');
            $employee->total_paid = $totalSalaryPaid + $totalAdvancePaid;

            // Calculate expected salary based on employee type
            if ($employee->isContractual()) {
                $employee->calculated_salary = $employee->calculateContractualSalary($previousMonth);
                $employee->present_days = $employee->getPresentDaysInMonth($previousMonth);
            } else {
                $employee->calculated_salary = $employee->salary_amount;
                $employee->present_days = null;
            }

            // Check if previous month salary is paid
            $previousMonthSalaryPaid = $employee->salaries()
                ->where('month', $previousMonth)
                ->sum('amount');

            // Due = this month's salary - (salary paid for this month + total advance not yet deducted)
            // Advance reduces the due amount
            $undeductedAdvance = $employee->advances()->where('is_deducted', false)->sum('amount');
            $employee->current_month_due = max(0, $employee->calculated_salary - $previousMonthSalaryPaid - $undeductedAdvance);
            $employee->current_month_paid = $previousMonthSalaryPaid;

            // Also store individual totals
            $employee->total_salary_paid = $totalSalaryPaid;
            $employee->total_advance_paid = $totalAdvancePaid;
        });

        return response()->json($employees);
    }

    public function store(Request $request)
    {
        $rules = [
            'employee_type' => 'required|in:regular,contractual',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'joining_date' => 'nullable|date',
        ];

        // Conditional validation based on employee type
        if ($request->employee_type === 'regular') {
            $rules['project_id'] = 'required|exists:projects,id';
            $rules['salary_amount'] = 'required|numeric|min:0';
        } else {
            $rules['project_id'] = 'nullable|exists:projects,id';
            $rules['daily_rate'] = 'required|numeric|min:0';
        }

        $request->validate($rules);

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
        $rules = [
            'employee_type' => 'required|in:regular,contractual',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'joining_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ];

        // Conditional validation based on employee type
        if ($request->employee_type === 'regular') {
            $rules['project_id'] = 'required|exists:projects,id';
            $rules['salary_amount'] = 'required|numeric|min:0';
        } else {
            $rules['project_id'] = 'nullable|exists:projects,id';
            $rules['daily_rate'] = 'required|numeric|min:0';
        }

        $request->validate($rules);

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

    public function calculateSalary(Request $request, Employee $employee)
    {
        $request->validate([
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $month = $request->month;

        if ($employee->isRegular()) {
            return response()->json([
                'employee_type' => 'regular',
                'salary_amount' => $employee->salary_amount,
                'calculated_salary' => $employee->salary_amount,
                'month' => $month,
            ]);
        }

        // Contractual calculation
        [$year, $monthNum] = explode('-', $month);

        $presentDays = $employee->attendances()
            ->whereMonth('date', $monthNum)
            ->whereYear('date', $year)
            ->where('status', 'present')
            ->count();

        $totalDays = $employee->attendances()
            ->whereMonth('date', $monthNum)
            ->whereYear('date', $year)
            ->count();

        $calculatedSalary = $presentDays * floatval($employee->daily_rate);

        return response()->json([
            'employee_type' => 'contractual',
            'daily_rate' => $employee->daily_rate,
            'present_days' => $presentDays,
            'total_days' => $totalDays,
            'calculated_salary' => $calculatedSalary,
            'month' => $month,
        ]);
    }

    /**
     * Calculate number of months worked (from joining to previous month)
     * Previous month because current month salary is not due yet
     */
    private function getMonthsWorked($employee)
    {
        if (!$employee->joining_date) {
            return 0;
        }

        $joiningDate = \Carbon\Carbon::parse($employee->joining_date)->startOfMonth();
        // Previous month (salary is paid next month)
        $currentMonth = now()->subMonthNoOverflow()->startOfMonth();

        if ($joiningDate > $currentMonth) {
            return 0;
        }

        return $joiningDate->diffInMonths($currentMonth) + 1;
    }
}
