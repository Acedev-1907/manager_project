<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Cache Service
 * 
 * Centralized caching service với Strategy Pattern
 * Giúp quản lý cache dễ dàng và consistent
 */
class CacheService
{
    /**
     * Cache TTL mặc định (30 phút)
     */
    private const DEFAULT_TTL = 1800;

    /**
     * Cache keys
     */
    private const PINNED_PROJECT_KEY = 'user:%d:pinned_project';
    private const PROJECT_COUNT_KEY = 'user:%d:project_count';
    private const PROJECT_KEY = 'project:%s';
    private const USER_PROJECTS_KEY = 'user:%d:projects:%s';

    /**
     * Remember cache với callback
     * 
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        return Cache::remember($key, $ttl ?? self::DEFAULT_TTL, $callback);
    }

    /**
     * Get cache value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }

    /**
     * Set cache value
     * 
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        return Cache::put($key, $value, $ttl ?? self::DEFAULT_TTL);
    }

    /**
     * Forget cache
     * 
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Forget multiple cache keys
     * 
     * @param array $keys
     * @return void
     */
    public function forgetMany(array $keys): void
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Get pinned project cache key
     * 
     * @param int $userId
     * @return string
     */
    public function getPinnedProjectKey(int $userId): string
    {
        return sprintf(self::PINNED_PROJECT_KEY, $userId);
    }

    /**
     * Get project count cache key
     * 
     * @param int $userId
     * @return string
     */
    public function getProjectCountKey(int $userId): string
    {
        return sprintf(self::PROJECT_COUNT_KEY, $userId);
    }

    /**
     * Get project cache key
     * 
     * @param string $slug
     * @return string
     */
    public function getProjectKey(string $slug): string
    {
        return sprintf(self::PROJECT_KEY, $slug);
    }

    /**
     * Get user projects cache key
     * 
     * @param int $userId
     * @param string $query
     * @return string
     */
    public function getUserProjectsKey(int $userId, string $query = ''): string
    {
        return sprintf(self::USER_PROJECTS_KEY, $userId, md5($query));
    }

    /**
     * Clear user-related cache
     * 
     * @param int $userId
     * @return void
     */
    public function clearUserCache(int $userId): void
    {
        $this->forgetMany([
            $this->getPinnedProjectKey($userId),
            $this->getProjectCountKey($userId),
        ]);
    }

    /**
     * Clear project-related cache
     * 
     * @param string $slug
     * @return void
     */
    public function clearProjectCache(string $slug): void
    {
        $this->forget($this->getProjectKey($slug));
    }
}

