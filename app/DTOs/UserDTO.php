<?php

namespace App\DTOs;

use App\Models\User;

class UserDTO
{
    public ?int $id;
    public string $name;
    public string $email;
    public ?string $avatar;
    public ?string $bio;
    public ?string $social_id;
    public ?string $social_type;
    public bool $is_active;
    public ?string $email_verified_at;
    public ?string $last_login_at;
    public ?string $last_login_ip;
    public ?string $created_at;
    public ?string $updated_at;
    public ?int $completed_quizzes;
    public ?float $average_score;
    public ?int $total_points;
    public ?int $rank;
    public ?array $roles;
    public ?array $achievements;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->avatar = $data['avatar'] ?? null;
        $this->bio = $data['bio'] ?? null;
        $this->social_id = $data['social_id'] ?? null;
        $this->social_type = $data['social_type'] ?? null;
        $this->is_active = $data['is_active'] ?? true;
        $this->email_verified_at = $data['email_verified_at'] ?? null;
        $this->last_login_at = $data['last_login_at'] ?? null;
        $this->last_login_ip = $data['last_login_ip'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->completed_quizzes = $data['completed_quizzes'] ?? null;
        $this->average_score = $data['average_score'] ?? null;
        $this->total_points = $data['total_points'] ?? null;
        $this->rank = $data['rank'] ?? null;
        $this->roles = $data['roles'] ?? null;
        $this->achievements = $data['achievements'] ?? null;
    }

    public static function fromModel(User $user): self
    {
        return new self([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'bio' => $user->bio,
            'social_id' => $user->social_id,
            'social_type' => $user->social_type,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
            'last_login_at' => $user->last_login_at?->toDateTimeString(),
            'last_login_ip' => $user->last_login_ip,
            'created_at' => $user->created_at?->toDateTimeString(),
            'updated_at' => $user->updated_at?->toDateTimeString(),
            'completed_quizzes' => $user->completed_quizzes_count,
            'average_score' => $user->average_score,
            'total_points' => $user->leaderboard?->total_points,
            'rank' => $user->leaderboard?->rank,
            'roles' => $user->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ])->toArray(),
            'achievements' => $user->achievements->map(fn($achievement) => [
                'id' => $achievement->id,
                'name' => $achievement->name,
                'icon' => $achievement->icon,
                'earned_at' => $achievement->pivot->earned_at?->toDateTimeString(),
            ])->toArray(),
        ]);
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'social_id' => $this->social_id,
            'social_type' => $this->social_type,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'last_login_ip' => $this->last_login_ip,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getInitials(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        
        return substr($initials, 0, 2);
    }

    public function isAdmin(): bool
    {
        if (!$this->roles) {
            return false;
        }
        
        foreach ($this->roles as $role) {
            if ($role['name'] === 'admin') {
                return true;
            }
        }
        
        return false;
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function isOnline(): bool
    {
        if (!$this->last_login_at) {
            return false;
        }
        
        $lastLogin = \Carbon\Carbon::parse($this->last_login_at);
        return $lastLogin->diffInMinutes(now()) < 5;
    }
}