<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class InvestLoanLiability extends Model
{
    protected $fillable = [
        'partner_id',
        'name',
        'phone',
        'contact_person',
        'address',
        'type',
        'amount',
        'share_value',
        'number_of_shares',
        'face_value_per_share',
        'premium_value_per_share',
        'current_rate_per_share',
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
        'withdraw_date',
        'description',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'share_value' => 'decimal:2',
        'number_of_shares' => 'integer',
        'face_value_per_share' => 'decimal:2',
        'premium_value_per_share' => 'decimal:2',
        'current_rate_per_share' => 'decimal:2',
        'honorarium' => 'decimal:2',
        'invest_period' => 'integer',
        'profit_rate' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'total_payable' => 'decimal:2',
        'date' => 'date',
        'appoint_date' => 'date',
        'due_date' => 'date',
        'withdraw_date' => 'date',
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
        return $this->total_payable - $this->loanPayments()->sum('amount');
    }

    public function getTotalShareValueAttribute()
    {
        if ($this->number_of_shares && $this->face_value_per_share !== null) {
            return $this->number_of_shares * ($this->face_value_per_share + ($this->premium_value_per_share ?? 0));
        }
        return 0;
    }

    public function getCurrentValueAttribute()
    {
        if ($this->number_of_shares && $this->current_rate_per_share !== null) {
            return $this->number_of_shares * $this->current_rate_per_share;
        }
        return 0;
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
