<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReturnBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_return_id',
        'stock_batch_id',
        'quantity',
        'cost_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    public function productReturn()
    {
        return $this->belongsTo(ProductReturn::class);
    }

    public function stockBatch()
    {
        return $this->belongsTo(StockBatch::class);
    }
}
