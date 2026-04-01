<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\Warehouse;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'cost_price',
        'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batches()
    {
        return $this->hasMany(SaleItemBatch::class);
    }

    // Get cost of goods sold for this sale item
    public function getCostOfGoodsSoldAttribute(): float
    {
        return $this->batches->sum(fn($b) => $b->quantity * $b->cost_price);
    }

    // Get profit for this sale item
    public function getProfitAttribute(): float
    {
        return $this->total - $this->cost_of_goods_sold;
    }

    protected static function booted()
    {
        static::created(function ($item) {
            // Decrement global product stock
            $item->product->decrement('stock_quantity', $item->quantity);
            $item->syncWarehouseStock(false);
        });

        static::deleted(function ($item) {
            // Increment global product stock
            $item->product->increment('stock_quantity', $item->quantity);
            $item->syncWarehouseStock(true);

            // Restore batch quantities
            foreach ($item->batches as $saleItemBatch) {
                $stockBatch = $saleItemBatch->stockBatch;
                if ($stockBatch) {
                    $stockBatch->increment('remaining_quantity', $saleItemBatch->quantity);
                    $stockBatch->updateStatus();
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

    private function syncWarehouseStock(bool $increment): void
    {
        $warehouse = $this->resolveSourceWarehouse();

        if (!$warehouse) {
            return;
        }

        $warehouse->updateStock($this->product_id, $this->quantity, $increment);
    }

    private function resolveSourceWarehouse(): ?Warehouse
    {
        $sale = $this->relationLoaded('sale') ? $this->sale : $this->sale()->first();

        if (!$sale) {
            return null;
        }

        if ($sale->warehouse_id) {
            return Warehouse::find($sale->warehouse_id);
        }

        if ($sale->project_id) {
            return Warehouse::where('project_id', $sale->project_id)->first();
        }

        return null;
    }
}
