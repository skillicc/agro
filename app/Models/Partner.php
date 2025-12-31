<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'type',
        'phone',
        'email',
        'address',
        'share_percentage',
        'total_investment',
        'total_withdrawn',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'share_percentage' => 'decimal:2',
        'total_investment' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    public function updateBalance()
    {
        $this->total_investment = $this->investments()->where('type', 'investment')->sum('amount');
        $this->total_withdrawn = $this->investments()->where('type', 'withdrawal')->sum('amount');
        $this->current_balance = $this->total_investment - $this->total_withdrawn;
        $this->save();
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
