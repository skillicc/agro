<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Salary;
use App\Models\Advance;
use App\Models\SalaryAdjustment;
use App\Models\EmployeeBonus;
use App\Models\EmployeeEmploymentPeriod;
use App\Models\EmployeeWorkingDayOverride;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('project');

        // Exclude Administration employees by default
        $query->whereDoesntHave('project', function ($q) {
            $q->where('name', 'Administration');
        });

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

            $salaryDetails = $employee->calculateMonthlySalaryDetails($previousMonth);
            $employee->calculated_salary = floatval($salaryDetails['calculated_salary'] ?? 0);
            $employee->present_days = $employee->isContractual()
                ? ($salaryDetails['present_days'] ?? null)
                : null;
            $employee->salary_amount = floatval($salaryDetails['salary_amount'] ?? $employee->salary_amount);

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

            // Get absent dates for current month (for EL tooltip)
            $employee->absent_dates = $employee->attendances()
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->where('status', 'absent')
                ->pluck('date')
                ->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))
                ->toArray();

            // EL balance - auto calculated based on joining date (5 days/month for regular)
            $employee->el_balance = $employee->calculated_earn_leave;
        });

        return response()->json($employees);
    }

    // Get Administration employees with detailed salary info
    public function adminEmployees(Request $request)
    {
        // Position hierarchy order
        $positionOrder = [
            'Founder & CEO' => 1,
            'Director (IT & Accounts)' => 2,
            'Production Manager' => 3,
        ];

        $employees = Employee::with('project')
            ->whereHas('project', function ($q) {
                $q->where('name', 'Administration');
            })
            ->get()
            ->sortBy(function ($employee) use ($positionOrder) {
                return $positionOrder[$employee->position] ?? 999;
            })
            ->values();

        $currentMonth = now()->format('Y-m');
        $previousMonth = now()->subMonthNoOverflow()->format('Y-m');

        $employees->each(function ($employee) use ($currentMonth, $previousMonth) {
            $salaryDetails = $employee->calculateMonthlySalaryDetails($currentMonth);

            // Total salary paid all time
            $totalSalaryPaid = $employee->salaries()->sum('amount');

            // Total advance paid all time
            $totalAdvancePaid = $employee->advances()->sum('amount');

            // Undeducted advance
            $undeductedAdvance = $employee->advances()->where('is_deducted', false)->sum('amount');

            // Salary amount
            $employee->salary = floatval($salaryDetails['salary_amount'] ?? $employee->salary_amount);
            $employee->salary_amount = $employee->salary;
            $employee->calculated_salary = floatval($salaryDetails['calculated_salary'] ?? $employee->salary);

            // Earn Leave balance - auto calculated based on joining date (5 days/month for regular)
            $employee->el_balance = $employee->calculated_earn_leave;

            // Absent count this month
            $employee->absent_count = $employee->attendances()
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->where('status', 'absent')
                ->count();

            // Total advance (undeducted)
            $employee->advance_balance = $undeductedAdvance;

            // This month's salary paid
            $thisMonthPaid = $employee->salaries()
                ->where('month', $currentMonth)
                ->sum('amount');
            $employee->paid_this_month = $thisMonthPaid;

            // Due = salary - paid this month - undeducted advance
            $employee->due = max(0, floatval($employee->calculated_salary) - $thisMonthPaid - $undeductedAdvance);

            // Total paid all time
            $employee->total_paid = $totalSalaryPaid;
            $employee->total_advance = $totalAdvancePaid;
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
        $this->syncInitialEmploymentPeriod($employee, $request->user()?->id);

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
        $this->syncInitialEmploymentPeriod($employee, $request->user()?->id);

        return response()->json($employee->load('project'));
    }

    private function syncInitialEmploymentPeriod(Employee $employee, ?int $userId = null): void
    {
        if (!$employee->joining_date) {
            return;
        }

        $periods = $employee->employmentPeriods()->orderBy('start_date')->get();

        if ($periods->isEmpty()) {
            EmployeeEmploymentPeriod::create([
                'employee_id' => $employee->id,
                'start_date' => $employee->joining_date,
                'note' => 'Initial employment period',
                'created_by' => $userId,
            ]);
            return;
        }

        if ($periods->count() === 1) {
            $firstPeriod = $periods->first();
            if ($firstPeriod && $firstPeriod->start_date?->format('Y-m-d') !== $employee->joining_date->format('Y-m-d')) {
                $firstPeriod->update(['start_date' => $employee->joining_date]);
            }
        }
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

        $salaryDetails = $employee->calculateMonthlySalaryDetails($request->month);
        $expectedSalary = floatval($salaryDetails['calculated_salary']);

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

        return response()->json($employee->calculateMonthlySalaryDetails($month));
    }

    public function employmentPeriods(Employee $employee)
    {
        return response()->json(
            $employee->employmentPeriods()->with('creator')->orderByDesc('start_date')->get()
        );
    }

    public function storeEmploymentPeriod(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'note' => 'nullable|string|max:255',
        ]);

        $this->validateEmploymentPeriodOverlap($employee, $validated['start_date'], $validated['end_date'] ?? null);

        $period = EmployeeEmploymentPeriod::create([
            'employee_id' => $employee->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'note' => $validated['note'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($period->load('creator'), 201);
    }

    public function updateEmploymentPeriod(Request $request, EmployeeEmploymentPeriod $employmentPeriod)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'note' => 'nullable|string|max:255',
        ]);

        $this->validateEmploymentPeriodOverlap(
            $employmentPeriod->employee,
            $validated['start_date'],
            $validated['end_date'] ?? null,
            $employmentPeriod->id
        );

        $employmentPeriod->update($validated);

        return response()->json($employmentPeriod->load('creator'));
    }

    public function deleteEmploymentPeriod(EmployeeEmploymentPeriod $employmentPeriod)
    {
        $employmentPeriod->delete();

        return response()->json(['message' => 'Employment period deleted successfully']);
    }

    private function validateEmploymentPeriodOverlap(Employee $employee, string $startDate, ?string $endDate = null, ?int $ignorePeriodId = null): void
    {
        $query = $employee->employmentPeriods()
            ->when($ignorePeriodId, fn ($q) => $q->where('id', '!=', $ignorePeriodId))
            ->whereDate('start_date', '<=', $endDate ?? '9999-12-31')
            ->where(function ($query) use ($startDate) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $startDate);
            });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_date' => 'This employment period overlaps with an existing period for the employee.',
            ]);
        }
    }

    public function upsertWorkingDayOverride(Request $request, Employee $employee)
    {
        $request->validate([
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'worked_days' => 'required|integer|min:0|max:31',
            'note' => 'nullable|string|max:255',
        ]);

        if (Employee::usesAttendanceForMonth($request->month)) {
            return response()->json([
                'message' => 'Manual worked days are only allowed for months before 2026-01.',
            ], 422);
        }

        $override = EmployeeWorkingDayOverride::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'month' => $request->month,
            ],
            [
                'worked_days' => $request->worked_days,
                'note' => $request->note,
                'created_by' => $request->user()->id,
            ]
        );

        return response()->json([
            'message' => 'Worked days saved successfully.',
            'override' => $override->load('creator'),
            'salary_details' => $employee->fresh()->calculateMonthlySalaryDetails($request->month),
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
     * - Administration employees get 6 days leave allowance per month
     * - EL = allowance - absent days (current month only, not accumulated)
     */
    public function calculateEarnLeave(Request $request)
    {
        // Use current month
        $year = now()->year;
        $monthNum = now()->month;
        $month = now()->format('Y-m');

        // Get all active regular employees (only regular employees have EL)
        // Including Administration employees
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

            // Administration employees get 6 days, others get 5 days
            $isAdministration = $employee->project && $employee->project->name === 'Administration';
            $leaveAllowance = $isAdministration ? 6 : 5;

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
