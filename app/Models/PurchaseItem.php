<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'size',
        'package_qty',
        'unit_per_package',
        'package_price',
        'quantity',
        'unit_price',
        'unit_mrp',
        'total',
        'total_mrp',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'unit_mrp' => 'decimal:2',
        'total' => 'decimal:2',
        'total_mrp' => 'decimal:2',
        'package_price' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted()
    {
        static::created(function ($item) {
            // Increment global product stock
            $item->product->increment('stock_quantity', $item->quantity);

            // Increment warehouse stock if warehouse is set
            if ($item->purchase->warehouse_id) {
                \App\Models\WarehouseStock::updateOrCreate(
                    [
                        'warehouse_id' => $item->purchase->warehouse_id,
                        'product_id' => $item->product_id,
                    ],
                    []
                )->increment('quantity', $item->quantity);
            }
        });

        static::deleted(function ($item) {
            // Decrement global product stock
            $item->product->decrement('stock_quantity', $item->quantity);

            // Decrement warehouse stock if warehouse is set
            if ($item->purchase->warehouse_id) {
                $warehouseStock = \App\Models\WarehouseStock::where('warehouse_id', $item->purchase->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($warehouseStock) {
                    $warehouseStock->decrement('quantity', $item->quantity);
                }
            }
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
