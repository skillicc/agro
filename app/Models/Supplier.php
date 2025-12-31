<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'total_purchase',
        'total_paid',
        'total_due',
        'is_active',
    ];

    protected $casts = [
        'total_purchase' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_due' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function accountsPayable()
    {
        return $this->hasMany(AccountsPayable::class);
    }

    public function updateBalance()
    {
        $this->total_purchase = $this->purchases()->sum('total');
        $this->total_paid = $this->payments()->sum('amount');
        $this->total_due = $this->total_purchase - $this->total_paid;
        $this->save();
    }

    public function updateTotals()
    {
        $this->total_purchases = $this->accountsPayable()->sum('total_amount');
        $this->total_paid = $this->accountsPayable()->sum('paid_amount');
        $this->total_due = $this->accountsPayable()->sum('outstanding_amount');
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
