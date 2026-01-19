<?php

namespace App\Models;

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

    public function isRegular(): bool
    {
        return $this->employee_type === 'regular';
    }

    public function isContractual(): bool
    {
        return $this->employee_type === 'contractual';
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
