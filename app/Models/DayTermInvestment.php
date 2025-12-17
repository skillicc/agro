<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DayTermInvestment extends Model
{
    protected $fillable = [
        'investor_name',
        'phone',
        'daily_amount',
        'total_days',
        'start_date',
        'end_date',
        'total_amount',
        'paid_amount',
        'return_amount',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'daily_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'return_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function payments()
    {
        return $this->hasMany(DayTermPayment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatePaidAmount()
    {
        $this->paid_amount = $this->payments()->sum('amount');
        $this->save();
    }
}
