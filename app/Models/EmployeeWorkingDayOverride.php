<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeWorkingDayOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'worked_days',
        'note',
        'created_by',
    ];

    protected $casts = [
        'worked_days' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
