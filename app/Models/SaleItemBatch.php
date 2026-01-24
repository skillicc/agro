<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItemBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_item_id',
        'stock_batch_id',
        'quantity',
        'cost_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function stockBatch()
    {
        return $this->belongsTo(StockBatch::class);
    }

    // Get total cost for this batch allocation
    public function getTotalCostAttribute(): float
    {
        return $this->quantity * $this->cost_price;
    }
}
