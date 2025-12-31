<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'project_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeForProjectType($query, $type)
    {
        return $query->where(function ($q) use ($type) {
            $q->where('project_type', $type)->orWhere('project_type', 'all');
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
