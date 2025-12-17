<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountsPayable extends Model
{
    protected $table = 'accounts_payable';

    protected $fillable = [
        'supplier_id',
        'purchase_id',
        'total_amount',
        'paid_amount',
        'outstanding_amount',
        'purchase_date',
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(AccountsPayablePayment::class);
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
            // Update supplier totals
            $model->supplier->updateTotals();
        });

        static::updated(function ($model) {
            $model->supplier->updateTotals();
        });
    }
}
