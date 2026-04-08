<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class ProductReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'warehouse_id',
        'product_id',
        'quantity',
        'value',
        'date',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'value' => 'decimal:2',
        'date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function batches()
    {
        return $this->hasMany(ProductReturnBatch::class);
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
