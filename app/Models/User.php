<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laratrust\Traits\LaratrustUserTrait;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements LaratrustUser
{
    use HasRolesAndPermissions, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'social_id',
        'social_type',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'social_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }

    public function leaderboard()
    {
        return $this->hasOne(Leaderboard::class);
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
                    ->withPivot('earned_at')
                    ->withTimestamps();
    }

    public function getCompletedQuizzesCountAttribute()
    {
        return $this->attempts()
                    ->where('status', 'completed')
                    ->count();
    }

    public function getAverageScoreAttribute()
    {
        return $this->attempts()
                    ->where('status', 'completed')
                    ->avg('percentage_score') ?? 0;
    }

    public function getTotalPointsAttribute()
    {
        return $this->leaderboard?->total_points ?? 0;
    }

    public function updateLastLogin()
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }
}