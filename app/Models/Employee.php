<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Employee extends Model
{
    use HasFactory;

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
        $presentDays = $this->getPresentDaysInMonth($month);
        [$year, $monthNum] = explode('-', $month);

        $totalDays = $this->attendances()
            ->whereMonth('date', $monthNum)
            ->whereYear('date', $year)
            ->count();

        $calculatedSalary = $baseSalary;
        $isProrated = false;

        if ($this->joining_date) {
            $joiningDate = Carbon::parse($this->joining_date);

            if ($joiningDate->gt($monthEnd)) {
                $calculatedSalary = 0;
                $isProrated = true;
            } elseif ($joiningDate->isSameMonth($monthStart)) {
                $workedDays = $presentDays > 0
                    ? $presentDays
                    : max(0, $joiningDate->diffInDays($monthEnd) + 1);

                $calculatedSalary = round(($baseSalary / $monthStart->daysInMonth) * $workedDays, 2);
                $isProrated = true;
            }
        }

        return [
            'employee_type' => 'regular',
            'salary_amount' => $baseSalary,
            'present_days' => $presentDays,
            'total_days' => $totalDays,
            'calculated_salary' => $calculatedSalary,
            'month' => $month,
            'is_prorated' => $isProrated,
        ];
    }

    public function calculateMonthlySalaryDetails(string $month): array
    {
        if ($this->isRegular()) {
            return $this->calculateRegularSalary($month);
        }

        [$year, $monthNum] = explode('-', $month);
        $presentDays = $this->getPresentDaysInMonth($month);
        $totalDays = $this->attendances()
            ->whereMonth('date', $monthNum)
            ->whereYear('date', $year)
            ->count();

        return [
            'employee_type' => 'contractual',
            'daily_rate' => floatval($this->daily_rate),
            'present_days' => $presentDays,
            'total_days' => $totalDays,
            'calculated_salary' => $this->calculateContractualSalary($month),
            'month' => $month,
            'is_prorated' => false,
        ];
    }

    public function calculateContractualSalary(string $month): float
    {
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
