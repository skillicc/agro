<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestLoanLiability extends Model
{
    protected $fillable = [
        'name',
        'type',
        'amount',
        'date',
        'due_date',
        'description',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'due_date' => 'date',
    ];
}
