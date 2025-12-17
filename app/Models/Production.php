<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

        static::deleted(function ($production) {
            $production->product->decrement('stock_quantity', $production->quantity);
        });
    }
}
