<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * SpamBlock Model
 * 
 * Stores spam block information in database
 */
class SpamBlock extends Model
{
    protected $fillable = [
        'factor_type',
        'action_type',
        'identifier',
        'blocked_until',
        'attempt_count',
        'metadata',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
        'metadata' => 'array',
        'attempt_count' => 'integer',
    ];

    /**
     * Check if block is still active (not expired)
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        if (!$this->blocked_until) {
            return true; // Permanent block (if needed)
        }
        return $this->blocked_until->isFuture();
    }

    /**
     * Get remaining minutes until block expires
     * 
     * @return int
     */
    public function getRemainingMinutes(): int
    {
        if (!$this->blocked_until || $this->blocked_until->isPast()) {
            return 0;
        }
        return max(0, Carbon::now()->diffInMinutes($this->blocked_until));
    }

    /**
     * Scope to get active blocks only
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('blocked_until')
              ->orWhere('blocked_until', '>', now());
        });
    }

    /**
     * Scope to get expired blocks
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('blocked_until')
                     ->where('blocked_until', '<=', now());
    }

    /**
     * Find or create block for a factor
     * 
     * @param string $factorType
     * @param string $actionType
     * @param string|int $identifier
     * @param int $blockDuration Minutes to block
     * @param int $attemptCount
     * @return self
     */
    public static function findOrCreateBlock(
        string $factorType,
        string $actionType,
        $identifier,
        int $blockDuration,
        int $attemptCount = 0
    ): self {
        $blockedUntil = now()->addMinutes($blockDuration);

        return self::updateOrCreate(
            [
                'factor_type' => $factorType,
                'action_type' => $actionType,
                'identifier' => (string) $identifier,
            ],
            [
                'blocked_until' => $blockedUntil,
                'attempt_count' => $attemptCount,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Remove block for a factor
     * 
     * @param string $factorType
     * @param string $actionType
     * @param string|int $identifier
     * @return bool
     */
    public static function removeBlock(
        string $factorType,
        string $actionType,
        $identifier
    ): bool {
        return self::where('factor_type', $factorType)
            ->where('action_type', $actionType)
            ->where('identifier', (string) $identifier)
            ->delete() > 0;
    }

    /**
     * Clean up expired blocks
     * 
     * @return int Number of deleted blocks
     */
    public static function cleanupExpired(): int
    {
        return self::expired()->delete();
    }
}
