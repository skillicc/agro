<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class InvestLoanLiability extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'contact_person',
        'type',
        'amount',
        'share_value',
        'honorarium',
        'honorarium_type',
        'invest_period',
        'profit_rate',
        'loan_type',
        'received_amount',
        'total_payable',
        'receive_date',
        'date',
        'appoint_date',
        'due_date',
        'description',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'share_value' => 'decimal:2',
        'honorarium' => 'decimal:2',
        'invest_period' => 'integer',
        'profit_rate' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'total_payable' => 'decimal:2',
        'date' => 'date',
        'appoint_date' => 'date',
        'due_date' => 'date',
        'receive_date' => 'date',
    ];

    public function payments()
    {
        return $this->hasMany(InvestLoanLiabilityPayment::class);
    }

    public function sharePayments()
    {
        return $this->hasMany(InvestLoanLiabilityPayment::class)->where('type', 'share_payment');
    }

    public function profitWithdrawals()
    {
        return $this->hasMany(InvestLoanLiabilityPayment::class)->where('type', 'profit_withdrawal');
    }

    public function honorariumPayments()
    {
        return $this->hasMany(InvestLoanLiabilityPayment::class)->where('type', 'honorarium_payment');
    }

    public function loanPayments()
    {
        return $this->hasMany(InvestLoanLiabilityPayment::class)->where('type', 'loan_payment');
    }

    public function getTotalSharePaidAttribute()
    {
        return $this->sharePayments()->sum('amount');
    }

    public function getTotalProfitWithdrawnAttribute()
    {
        return $this->profitWithdrawals()->sum('amount');
    }

    public function getTotalLoanPaidAttribute()
    {
        return $this->loanPayments()->sum('amount');
    }

    public function getLoanRestAmountAttribute()
    {
        $totalPayable = $this->loan_type === 'with_profit' ? $this->total_payable : $this->received_amount;
        return $totalPayable - $this->loanPayments()->sum('amount');
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
