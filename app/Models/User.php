<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;
    const IS_VALID_EMAIL = 1;

    const IS_INVALID_EMAIL = 0;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'isValidEmail',
        'remember_token',
        'friend_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        // 'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Remove Vietnamese accents from a string
     * @deprecated Use App\Helpers\StringHelper::removeAccents() instead
     */
    public static function remove_accents($str)
    {
        return \App\Helpers\StringHelper::removeAccents($str);
    }

    public function projects()
    {
        return $this->belongsToMany(\App\Models\Project::class, 'project_user', 'user_id', 'project_id');
    }

    public function posts()
    {
        return $this->hasMany(\App\Models\Post::class);
    }
}
