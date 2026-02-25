<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'warehouse_id',
        'customer_id',
        'challan_no',
        'date',
        'subtotal',
        'discount',
        'total',
        'paid',
        'due',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid' => 'decimal:2',
        'due' => 'decimal:2',
        'date' => 'date',
    ];

    protected static function booted()
    {
        static::saved(function ($sale) {
            if ($sale->customer_id) {
                $sale->customer->updateBalance();
            }
        });

        static::deleted(function ($sale) {
            if ($sale->customer_id) {
                $sale->customer->updateBalance();
            }
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function calculateTotals()
    {
        $this->subtotal = $this->items()->sum('total');
        $this->total = $this->subtotal - $this->discount;
        $this->due = $this->total - $this->paid;
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
