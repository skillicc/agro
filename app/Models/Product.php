<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'type',
        'project_type',
        'unit',
        'buying_price',
        'production_cost',
        'selling_price',
        'stock_quantity',
        'alert_quantity',
        'description',
        'is_active',
    ];

    protected $casts = [
        'buying_price' => 'decimal:2',
        'production_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Check if product is own production
    public function isOwnProduction(): bool
    {
        return $this->type === 'own_production';
    }

    // Get cost price (buying_price for trading, production_cost for own production)
    public function getCostPrice(): float
    {
        return $this->isOwnProduction()
            ? (float) ($this->production_cost ?? 0)
            : (float) ($this->buying_price ?? 0);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function damages()
    {
        return $this->hasMany(Damage::class);
    }

    public function productions()
    {
        return $this->hasMany(Production::class);
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
