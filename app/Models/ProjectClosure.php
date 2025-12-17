<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectClosure extends Model
{
    protected $fillable = [
        'project_id',
        'closure_date',
        'total_sales',
        'total_harvest_value',
        'other_income',
        'total_income',
        'total_purchases',
        'total_expenses',
        'total_salaries',
        'total_damages',
        'total_cost',
        'gross_profit',
        'net_profit',
        'profit_percentage',
        'partner_shares',
        'summary',
        'remarks',
        'closed_by',
    ];

    protected $casts = [
        'closure_date' => 'date',
        'total_sales' => 'decimal:2',
        'total_harvest_value' => 'decimal:2',
        'other_income' => 'decimal:2',
        'total_income' => 'decimal:2',
        'total_purchases' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'total_salaries' => 'decimal:2',
        'total_damages' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
        'partner_shares' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
