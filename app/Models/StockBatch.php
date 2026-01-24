<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'purchase_item_id',
        'production_id',
        'warehouse_id',
        'batch_number',
        'unit_price',
        'unit_mrp',
        'initial_quantity',
        'remaining_quantity',
        'purchase_date',
        'expiry_date',
        'source',
        'status',
        'note',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'unit_mrp' => 'decimal:2',
        'initial_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'purchase_date' => 'date',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function saleItemBatches()
    {
        return $this->hasMany(SaleItemBatch::class);
    }

    // Scope for FIFO ordering (oldest first)
    public function scopeFifo($query)
    {
        return $query->orderBy('purchase_date', 'asc')->orderBy('id', 'asc');
    }

    // Scope for active batches with remaining stock
    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
                     ->where('remaining_quantity', '>', 0);
    }

    // Check if batch is depleted
    public function isDepleted(): bool
    {
        return $this->remaining_quantity <= 0;
    }

    // Update status based on remaining quantity
    public function updateStatus(): void
    {
        if ($this->remaining_quantity <= 0) {
            $this->update(['status' => 'depleted']);
        } elseif ($this->status === 'depleted') {
            $this->update(['status' => 'active']);
        }
    }

    // Generate batch number
    public static function generateBatchNumber($productId): string
    {
        $prefix = 'BTH-' . $productId . '-' . date('Ymd');
        $count = static::where('batch_number', 'like', $prefix . '%')->count();
        return $prefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    // Get total value of this batch
    public function getTotalValueAttribute(): float
    {
        return $this->remaining_quantity * $this->unit_price;
    }

    // Get sold quantity
    public function getSoldQuantityAttribute(): float
    {
        return $this->initial_quantity - $this->remaining_quantity;
    }
}
