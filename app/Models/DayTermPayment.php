<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class DayTermPayment extends Model
{
    protected $fillable = [
        'day_term_investment_id',
        'amount',
        'payment_date',
        'day_number',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function dayTermInvestment()
    {
        return $this->belongsTo(DayTermInvestment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted()
    {
        static::saved(function ($payment) {
            $payment->dayTermInvestment->updatePaidAmount();
        });

        static::deleted(function ($payment) {
            $payment->dayTermInvestment->updatePaidAmount();
        });
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
