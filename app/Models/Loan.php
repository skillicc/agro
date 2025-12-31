<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Loan extends Model
{
    protected $fillable = [
        'lender_name',
        'phone',
        'address',
        'principal_amount',
        'interest_rate',
        'loan_date',
        'due_date',
        'tenure_months',
        'total_paid',
        'outstanding_balance',
        'status',
        'reference_no',
        'note',
        'created_by',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'loan_date' => 'date',
        'due_date' => 'date',
    ];

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updateBalance()
    {
        $this->total_paid = $this->payments()->sum('amount');
        $this->outstanding_balance = $this->principal_amount - $this->total_paid;
        $this->status = $this->outstanding_balance <= 0 ? 'paid' : 'active';
        $this->save();
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
