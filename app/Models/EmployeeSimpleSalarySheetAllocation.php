<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSimpleSalarySheetAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'allocated_amount',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
