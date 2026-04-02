<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandCultivation extends Model
{
    use HasFactory;

    protected $fillable = [
        'land_id',
        'project_id',
        'crop_name',
        'opening_date',
        'expected_closing_date',
        'closing_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'expected_closing_date' => 'date',
        'closing_date' => 'date',
    ];

    public function land()
    {
        return $this->belongsTo(Land::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
