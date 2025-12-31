<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'manager_name',
        'project_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'warehouse_stocks')
            ->withPivot('quantity', 'min_quantity')
            ->withTimestamps();
    }

    public function outgoingTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    public function incomingTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    // Get stock quantity for a specific product
    public function getStockQuantity($productId)
    {
        $stock = $this->stocks()->where('product_id', $productId)->first();
        return $stock ? $stock->quantity : 0;
    }

    // Update stock quantity
    public function updateStock($productId, $quantity, $increment = true)
    {
        $stock = $this->stocks()->firstOrCreate(
            ['product_id' => $productId],
            ['quantity' => 0, 'min_quantity' => 0]
        );

        if ($increment) {
            $stock->increment('quantity', $quantity);
        } else {
            $stock->decrement('quantity', $quantity);
        }

        return $stock;
    }

    // Check if stock is low
    public function getLowStockProducts()
    {
        return $this->stocks()
            ->whereRaw('quantity <= min_quantity')
            ->with('product')
            ->get();
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
