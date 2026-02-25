<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'total_sale',
        'total_paid',
        'total_due',
        'is_active',
    ];

    protected $casts = [
        'total_sale' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_due' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function accountsReceivable()
    {
        return $this->hasMany(AccountsReceivable::class);
    }

    public function updateBalance()
    {
        $this->total_sale = $this->sales()->sum('total');
        // total_paid = amount paid at time of sale + separate customer payments
        $this->total_paid = $this->sales()->sum('paid') + $this->payments()->sum('amount');
        // Due should only be positive (what customer owes us)
        // If paid more than sale, due = 0 (they have credit/advance)
        $this->total_due = max(0, $this->total_sale - $this->total_paid);
        $this->save();
    }

    public function updateTotals()
    {
        $this->total_sales = $this->accountsReceivable()->sum('total_amount');
        $this->total_paid = $this->accountsReceivable()->sum('paid_amount');
        $this->total_due = $this->accountsReceivable()->sum('outstanding_amount');
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
