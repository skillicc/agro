<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Employee extends Model
{
    use HasFactory;

    public const ATTENDANCE_SALARY_START_MONTH = '2026-01';

    protected $fillable = [
        'project_id',
        'employee_type',
        'name',
        'phone',
        'position',
        'salary_amount',
        'daily_rate',
        'joining_date',
        'earn_leave',
        'is_active',
    ];

    protected $casts = [
        'salary_amount' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'earn_leave' => 'decimal:2',
        'joining_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    public function advances()
    {
        return $this->hasMany(Advance::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function salaryAdjustments()
    {
        return $this->hasMany(SalaryAdjustment::class);
    }

    public function bonuses()
    {
        return $this->hasMany(EmployeeBonus::class);
    }

    public function workingDayOverrides()
    {
        return $this->hasMany(EmployeeWorkingDayOverride::class);
    }

    public function employmentPeriods()
    {
        return $this->hasMany(EmployeeEmploymentPeriod::class)->orderBy('start_date');
    }

    public function simpleSalarySheetAllocations()
    {
        return $this->hasMany(EmployeeSimpleSalarySheetAllocation::class)->orderBy('month');
    }

    public function isRegular(): bool
    {
        return $this->employee_type === 'regular';
    }

    /**
     * Calculate accumulated earned leave from January of current year
     * Regular employees earn 5 days per month, minus leaves taken
     */
    public function getCalculatedEarnLeaveAttribute(): float
    {
        if (!$this->isRegular()) {
            return 0;
        }

        $now = new \DateTime();
        $currentYear = (int) $now->format('Y');
        // Current month number (1-12)
        $currentMonth = (int) $now->format('n');

        // 5 days EL per month from January to current month
        $totalEarned = $currentMonth * 5;

        // Subtract absent, leave, sick_leave from EL
        $leavesTaken = $this->attendances()
            ->whereYear('date', $currentYear)
            ->whereIn('status', ['absent', 'leave', 'sick_leave'])
            ->count();

        return max(0, $totalEarned - $leavesTaken);
    }

    public function isContractual(): bool
    {
        return $this->employee_type === 'contractual';
    }

    public static function attendanceSalaryStartDate(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', self::ATTENDANCE_SALARY_START_MONTH . '-01')->startOfDay();
    }

    public static function usesAttendanceForMonth(string $month): bool
    {
        $monthStart = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();

        return $monthStart->gte(self::attendanceSalaryStartDate());
    }

    public function suggestedWorkedDaysForMonth(string $month): ?int
    {
        return $this->getEmploymentDaysForMonth($month);
    }

    public function getWorkedDaysOverrideForMonth(string $month): ?EmployeeWorkingDayOverride
    {
        if ($this->relationLoaded('workingDayOverrides')) {
            return $this->workingDayOverrides->firstWhere('month', $month);
        }

        return $this->workingDayOverrides()->where('month', $month)->first();
    }

    public function getEmploymentPeriodsForMonth(string $month)
    {
        $monthStart = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        if ($this->relationLoaded('employmentPeriods')) {
            return $this->employmentPeriods->filter(function ($period) use ($monthStart, $monthEnd) {
                $endDate = $period->end_date ? Carbon::parse($period->end_date)->endOfDay() : null;
                return Carbon::parse($period->start_date)->startOfDay()->lte($monthEnd)
                    && (!$endDate || $endDate->gte($monthStart));
            })->values();
        }

        return $this->employmentPeriods()
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->where(function ($query) use ($monthStart) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $monthStart->toDateString());
            })
            ->get();
    }

    public function getEmploymentDaysForMonth(string $month): int
    {
        $monthStart = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $periods = $this->getEmploymentPeriodsForMonth($month);
        $hasAnyEmploymentPeriods = $this->relationLoaded('employmentPeriods')
            ? $this->employmentPeriods->isNotEmpty()
            : $this->employmentPeriods()->exists();

        if ($periods->isEmpty()) {
            if ($hasAnyEmploymentPeriods) {
                return 0;
            }

            if ($this->joining_date) {
                $joiningDate = Carbon::parse($this->joining_date)->startOfDay();

                if ($joiningDate->gt($monthEnd)) {
                    return 0;
                }

                if ($joiningDate->isSameMonth($monthStart)) {
                    return max(0, $joiningDate->diffInDays($monthEnd) + 1);
                }

                return $monthStart->daysInMonth;
            }

            return 0;
        }

        $coveredDates = [];

        foreach ($periods as $period) {
            $start = Carbon::parse($period->start_date)->startOfDay()->max($monthStart);
            $end = $period->end_date
                ? Carbon::parse($period->end_date)->endOfDay()->min($monthEnd)
                : $monthEnd->copy();

            if ($start->gt($end)) {
                continue;
            }

            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $coveredDates[$cursor->format('Y-m-d')] = true;
                $cursor->addDay();
            }
        }

        return count($coveredDates);
    }

    public function getSalaryAmountForMonth(string $month): float
    {
        $monthEnd = Carbon::createFromFormat('Y-m-d', $month . '-01')->endOfMonth();
        $adjustments = $this->salaryAdjustments()
            ->orderBy('effective_date')
            ->get(['old_salary', 'new_salary', 'effective_date']);

        if ($adjustments->isEmpty()) {
            return floatval($this->salary_amount);
        }

        $latestAppliedAdjustment = $adjustments
            ->filter(fn ($adjustment) => Carbon::parse($adjustment->effective_date)->lte($monthEnd))
            ->last();

        if ($latestAppliedAdjustment) {
            return floatval($latestAppliedAdjustment->new_salary);
        }

        return floatval($adjustments->first()->old_salary);
    }

    public function calculateRegularSalary(string $month): array
    {
        $monthStart = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $baseSalary = $this->getSalaryAmountForMonth($month);
        $usesAttendance = self::usesAttendanceForMonth($month);
        $override = $this->getWorkedDaysOverrideForMonth($month);
        $suggestedWorkedDays = $this->suggestedWorkedDaysForMonth($month);
        $manualWorkedDays = $override?->worked_days;
        $presentDays = $usesAttendance ? $this->getPresentDaysInMonth($month) : 0;
        [$year, $monthNum] = explode('-', $month);
        $employmentDays = $this->getEmploymentDaysForMonth($month);

        $totalDays = $usesAttendance
            ? $this->attendances()
                ->whereMonth('date', $monthNum)
                ->whereYear('date', $year)
                ->count()
            : 0;

        $calculatedSalary = $baseSalary;
        $isProrated = false;
        $workedDays = $usesAttendance
            ? ($presentDays > 0 ? $presentDays : null)
            : ($manualWorkedDays ?? $suggestedWorkedDays);

        if ($employmentDays === 0) {
            $calculatedSalary = 0;
            $workedDays = 0;
            $isProrated = true;
        } elseif (!$usesAttendance && $workedDays !== null) {
            $workedDays = max(0, (int) $workedDays);
            $calculatedSalary = round(($baseSalary / $monthStart->daysInMonth) * min($workedDays, $monthStart->daysInMonth), 2);
            $isProrated = $workedDays < $monthStart->daysInMonth;
        } elseif ($employmentDays < $monthStart->daysInMonth && $presentDays > 0) {
            $workedDays = $presentDays;
            $calculatedSalary = round(($baseSalary / $monthStart->daysInMonth) * $workedDays, 2);
            $isProrated = true;
        }

        return [
            'employee_type' => 'regular',
            'salary_amount' => $baseSalary,
            'present_days' => $presentDays,
            'worked_days' => $workedDays,
            'total_days' => $totalDays,
            'calculated_salary' => $calculatedSalary,
            'month' => $month,
            'is_prorated' => $isProrated,
            'working_day_source' => $usesAttendance ? 'attendance' : ($manualWorkedDays !== null ? 'manual' : 'suggested'),
            'suggested_worked_days' => $usesAttendance ? null : $suggestedWorkedDays,
            'manual_worked_days' => $manualWorkedDays,
            'working_day_note' => $override?->note,
        ];
    }

    public function calculateMonthlySalaryDetails(string $month): array
    {
        if ($this->isRegular()) {
            return $this->calculateRegularSalary($month);
        }

        $usesAttendance = self::usesAttendanceForMonth($month);
        [$year, $monthNum] = explode('-', $month);
        $presentDays = $usesAttendance ? $this->getPresentDaysInMonth($month) : 0;
        $totalDays = $usesAttendance
            ? $this->attendances()
                ->whereMonth('date', $monthNum)
                ->whereYear('date', $year)
                ->count()
            : 0;
        $override = $this->getWorkedDaysOverrideForMonth($month);
        $suggestedWorkedDays = $this->suggestedWorkedDaysForMonth($month);
        $manualWorkedDays = $override?->worked_days;
        $workedDays = $usesAttendance ? $presentDays : ($manualWorkedDays ?? $suggestedWorkedDays ?? 0);

        return [
            'employee_type' => 'contractual',
            'daily_rate' => floatval($this->daily_rate),
            'present_days' => $presentDays,
            'worked_days' => $workedDays,
            'total_days' => $totalDays,
            'calculated_salary' => $usesAttendance ? $this->calculateContractualSalary($month) : ($workedDays * floatval($this->daily_rate)),
            'month' => $month,
            'is_prorated' => false,
            'working_day_source' => $usesAttendance ? 'attendance' : ($manualWorkedDays !== null ? 'manual' : 'suggested'),
            'suggested_worked_days' => $usesAttendance ? null : $suggestedWorkedDays,
            'manual_worked_days' => $manualWorkedDays,
            'working_day_note' => $override?->note,
        ];
    }

    public function calculateContractualSalary(string $month): float
    {
        if (!self::usesAttendanceForMonth($month)) {
            return 0;
        }

        [$year, $monthNum] = explode('-', $month);

        $presentDays = $this->attendances()
            ->whereMonth('date', $monthNum)
            ->whereYear('date', $year)
            ->where('status', 'present')
            ->count();

        return $presentDays * floatval($this->daily_rate);
    }

    public function getPresentDaysInMonth(string $month): int
    {
        if (!self::usesAttendanceForMonth($month)) {
            return 0;
        }

        [$year, $monthNum] = explode('-', $month);

        return $this->attendances()
            ->whereMonth('date', $monthNum)
            ->whereYear('date', $year)
            ->where('status', 'present')
            ->count();
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
