<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Production extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'product_id',
        'quantity',
        'cost',
        'date',
        'note',
        'created_by',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted()
    {
        static::created(function ($production) {
            $production->product->increment('stock_quantity', $production->quantity);
        });

        static::updated(function ($production) {
            $originalProductId = $production->getOriginal('product_id');
            $originalQuantity = (float) $production->getOriginal('quantity');
            $newQuantity = (float) $production->quantity;

            if ($originalProductId == $production->product_id) {
                $difference = $newQuantity - $originalQuantity;

                if ($difference > 0) {
                    $production->product->increment('stock_quantity', $difference);
                } elseif ($difference < 0) {
                    $production->product->decrement('stock_quantity', abs($difference));
                }
            } else {
                Product::where('id', $originalProductId)->decrement('stock_quantity', $originalQuantity);
                Product::where('id', $production->product_id)->increment('stock_quantity', $newQuantity);
            }
        });

        static::deleted(function ($production) {
            $production->product->decrement('stock_quantity', $production->quantity);
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
