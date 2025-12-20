<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'image_url',
        'images',
        'share_count',
    ];

    protected $casts = [
        'images' => 'array',
        'share_count' => 'integer',
    ];

    protected $appends = ['user_reaction_type'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the reaction type of the current authenticated user for this post
     * 
     * @return string|null
     */
    public function getUserReactionTypeAttribute(): ?string
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        
        if (!$userId) {
            return null;
        }

        // Check if likes relationship is loaded
        if ($this->relationLoaded('likes')) {
            $userLike = $this->likes->firstWhere('user_id', $userId);
            return $userLike ? $userLike->type : null;
        }

        // If not loaded, query directly
        $like = $this->likes()->where('user_id', $userId)->first();
        return $like ? $like->type : null;
    }
}
