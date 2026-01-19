<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class InvestLoanLiabilityPayment extends Model
{
    protected $fillable = [
        'invest_loan_liability_id',
        'type',
        'amount',
        'date',
        'for_year',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'for_year' => 'integer',
    ];

    public function investLoanLiability()
    {
        return $this->belongsTo(InvestLoanLiability::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
