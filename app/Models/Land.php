<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Land extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'location',
        'size',
        'unit',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'size' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }

    public function cultivations()
    {
        return $this->hasMany(LandCultivation::class);
    }

    public function currentCultivation()
    {
        return $this->hasOne(LandCultivation::class)->latestOfMany('opening_date');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
