<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Salary;
use App\Models\Advance;
use App\Models\SalaryAdjustment;
use App\Models\EmployeeBonus;
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

        // Check if salary already exists for this month
        $existingSalary = Salary::where('employee_id', $employee->id)
            ->where('month', $request->month)
            ->first();

        if ($existingSalary) {
            return response()->json([
                'message' => 'Salary for this month already exists. Please edit the existing entry.',
                'existing_salary' => $existingSalary
            ], 422);
        }

        // Calculate expected salary
        $expectedSalary = $employee->isContractual()
            ? $employee->calculateContractualSalary($request->month)
            : floatval($employee->salary_amount);

        $paidAmount = floatval($request->amount);
        $salaryAmount = min($paidAmount, $expectedSalary);
        $advanceAmount = max(0, $paidAmount - $expectedSalary);

        // Create salary entry
        $salary = Salary::create([
            'project_id' => $employee->project_id,
            'employee_id' => $employee->id,
            'amount' => $salaryAmount,
            'month' => $request->month,
            'payment_date' => $request->payment_date,
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        $advance = null;
        // If extra amount, create advance for next month
        if ($advanceAmount > 0) {
            // Calculate next month
            $nextMonth = \Carbon\Carbon::parse($request->month . '-01')->addMonth()->format('Y-m');

            $advance = Advance::create([
                'project_id' => $employee->project_id,
                'employee_id' => $employee->id,
                'amount' => $advanceAmount,
                'date' => $request->payment_date,
                'reason' => "Auto-advance from {$request->month} salary (Extra: ৳" . number_format($advanceAmount) . ")",
                'created_by' => $request->user()->id,
            ]);
        }

        return response()->json([
            'salary' => $salary->load(['employee', 'creator']),
            'advance' => $advance ? $advance->load(['employee', 'creator']) : null,
            'message' => $advance
                ? "Salary ৳" . number_format($salaryAmount) . " paid. Extra ৳" . number_format($advanceAmount) . " added as advance."
                : "Salary paid successfully."
        ], 201);
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

    /**
     * Calculate Earn Leave (EL) for all employees for current month
     * Rules:
     * - Regular employees get 5 days leave allowance per month
     * - EL = 5 - absent days (current month only, not accumulated)
     */
    public function calculateEarnLeave(Request $request)
    {
        // Use current month
        $year = now()->year;
        $monthNum = now()->month;
        $month = now()->format('Y-m');

        // Get all active regular employees (only regular employees have EL)
        $employees = Employee::with('project')
            ->where('is_active', true)
            ->where('employee_type', 'regular')
            ->get();

        $results = [];
        $totalUpdated = 0;

        foreach ($employees as $employee) {
            // Skip if employee joined after this month
            if ($employee->joining_date) {
                $joiningMonth = \Carbon\Carbon::parse($employee->joining_date)->format('Y-m');
                if ($joiningMonth > $month) {
                    $employee->earn_leave = 0;
                    $employee->save();
                    continue; // Employee hadn't joined yet
                }
            } else {
                // No joining date, set EL to 0
                $employee->earn_leave = 0;
                $employee->save();
                continue;
            }

            // All regular employees get 5 days leave allowance per month
            $leaveAllowance = 5;

            // Count all non-present days (absent, leave, sick_leave) in current month
            $absentDays = $employee->attendances()
                ->whereMonth('date', $monthNum)
                ->whereYear('date', $year)
                ->where('status', 'absent')
                ->count();

            $leaveDays = $employee->attendances()
                ->whereMonth('date', $monthNum)
                ->whereYear('date', $year)
                ->where('status', 'leave')
                ->count();

            $sickLeaveDays = $employee->attendances()
                ->whereMonth('date', $monthNum)
                ->whereYear('date', $year)
                ->where('status', 'sick_leave')
                ->count();

            // Total non-present days
            $totalLeaveDays = $absentDays + $leaveDays + $sickLeaveDays;

            // EL = allowance - absent days (current month only)
            $oldEL = floatval($employee->earn_leave ?? 0);
            $newEL = $leaveAllowance - $totalLeaveDays;

            $employee->earn_leave = $newEL;
            $employee->save();
            $totalUpdated++;

            $results[] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'project' => $employee->project?->name,
                'leave_allowance' => $leaveAllowance,
                'absent_days' => $absentDays,
                'leave_days' => $leaveDays,
                'sick_leave_days' => $sickLeaveDays,
                'total_leave_days' => $totalLeaveDays,
                'old_el' => $oldEL,
                'new_el' => $newEL,
            ];
        }

        return response()->json([
            'message' => "EL calculated for {$totalUpdated} employees",
            'month' => $month,
            'total_updated' => $totalUpdated,
            'results' => $results,
        ]);
    }

    /**
     * Adjust employee salary (increase or decrease)
     */
    public function adjustSalary(Request $request, Employee $employee)
    {
        $request->validate([
            'type' => 'required|in:increase,decrease',
            'new_salary' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldSalary = floatval($employee->salary_amount);
        $newSalary = floatval($request->new_salary);
        $amount = abs($newSalary - $oldSalary);

        // Create adjustment record
        $adjustment = SalaryAdjustment::create([
            'employee_id' => $employee->id,
            'type' => $request->type,
            'old_salary' => $oldSalary,
            'new_salary' => $newSalary,
            'amount' => $amount,
            'effective_date' => $request->effective_date,
            'reason' => $request->reason,
            'created_by' => $request->user()->id,
        ]);

        // Update employee salary
        $employee->salary_amount = $newSalary;
        $employee->save();

        return response()->json([
            'message' => 'Salary ' . ($request->type === 'increase' ? 'increased' : 'decreased') . ' successfully',
            'adjustment' => $adjustment->load(['employee', 'creator']),
        ], 201);
    }

    /**
     * Get salary adjustment history for an employee
     */
    public function salaryAdjustments(Employee $employee)
    {
        $adjustments = $employee->salaryAdjustments()
            ->with('creator')
            ->orderBy('effective_date', 'desc')
            ->get();

        return response()->json($adjustments);
    }

    /**
     * Give bonus or incentive to an employee
     */
    public function giveBonus(Request $request, Employee $employee)
    {
        $request->validate([
            'type' => 'required|in:bonus,incentive',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:255',
        ]);

        $bonus = EmployeeBonus::create([
            'employee_id' => $employee->id,
            'project_id' => $employee->project_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'date' => $request->date,
            'reason' => $request->reason,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => ucfirst($request->type) . ' given successfully',
            'bonus' => $bonus->load(['employee', 'project', 'creator']),
        ], 201);
    }

    /**
     * Get bonus/incentive history for an employee
     */
    public function employeeBonuses(Employee $employee)
    {
        $bonuses = $employee->bonuses()
            ->with(['project', 'creator'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($bonuses);
    }

    /**
     * Get all bonuses/incentives
     */
    public function allBonuses(Request $request)
    {
        $query = EmployeeBonus::with(['employee', 'project', 'creator']);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        return response()->json($query->orderBy('date', 'desc')->get());
    }

    /**
     * Delete a bonus/incentive
     */
    public function deleteBonus(EmployeeBonus $bonus)
    {
        $bonus->delete();
        return response()->json(['message' => 'Bonus/Incentive deleted successfully']);
    }
}
