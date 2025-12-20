<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * SpamAttempt Model
 * 
 * Tracks spam attempts for analytics and rate limiting
 */
class SpamAttempt extends Model
{
    protected $fillable = [
        'factor_type',
        'action_type',
        'identifier',
        'attempted_at',
        'metadata',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get attempts within time window
     * 
     * @param string $factorType
     * @param string $actionType
     * @param string|int $identifier
     * @param int $timeWindow Minutes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentAttempts(
        string $factorType,
        string $actionType,
        $identifier,
        int $timeWindow
    ) {
        $since = now()->subMinutes($timeWindow);

        return self::where('factor_type', $factorType)
            ->where('action_type', $actionType)
            ->where('identifier', (string) $identifier)
            ->where('attempted_at', '>=', $since)
            ->orderBy('attempted_at', 'desc')
            ->get();
    }

    /**
     * Count attempts within time window
     * 
     * @param string $factorType
     * @param string $actionType
     * @param string|int $identifier
     * @param int $timeWindow Minutes
     * @return int
     */
    public static function countRecentAttempts(
        string $factorType,
        string $actionType,
        $identifier,
        int $timeWindow
    ): int {
        return self::getRecentAttempts($factorType, $actionType, $identifier, $timeWindow)->count();
    }

    /**
     * Record an attempt
     * 
     * @param string $factorType
     * @param string $actionType
     * @param string|int $identifier
     * @param array|null $metadata
     * @return self
     */
    public static function recordAttempt(
        string $factorType,
        string $actionType,
        $identifier,
        ?array $metadata = null
    ): self {
        return self::create([
            'factor_type' => $factorType,
            'action_type' => $actionType,
            'identifier' => (string) $identifier,
            'attempted_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Clean up old attempts (older than X days)
     * 
     * @param int $days Number of days to keep
     * @return int Number of deleted attempts
     */
    public static function cleanupOldAttempts(int $days = 30): int
    {
        $cutoff = now()->subDays($days);
        return self::where('attempted_at', '<', $cutoff)->delete();
    }
}
