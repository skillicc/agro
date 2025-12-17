<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountsReceivable extends Model
{
    protected $table = 'accounts_receivable';

    protected $fillable = [
        'customer_id',
        'sale_id',
        'total_amount',
        'paid_amount',
        'outstanding_amount',
        'sale_date',
        'due_date',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(AccountsReceivablePayment::class);
    }

    public function updateBalance()
    {
        $this->paid_amount = $this->payments()->sum('amount');
        $this->outstanding_amount = $this->total_amount - $this->paid_amount;

        if ($this->outstanding_amount <= 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        }

        $this->save();
    }

    protected static function booted()
    {
        static::created(function ($model) {
            // Update customer totals
            $model->customer->updateTotals();
        });

        static::updated(function ($model) {
            $model->customer->updateTotals();
        });
    }
}
