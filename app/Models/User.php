<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class)->withPivot('permission')->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasProjectAccess(Project $project): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $this->projects()->where('project_id', $project->id)->exists();
    }

    public function canEditProject(Project $project): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        $pivot = $this->projects()->where('project_id', $project->id)->first();
        return $pivot && in_array($pivot->pivot->permission, ['full', 'read_write']);
    }
}
