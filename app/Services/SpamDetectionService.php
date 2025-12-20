<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Spam Detection Service
 * 
 * Centralized spam detection service for all actions.
 * Supports multiple detection factors:
 * - IP address
 * - Email domain
 * - User fingerprint (User-Agent + headers)
 * - User ID (for authenticated actions)
 * - Combined patterns
 * 
 * Usage:
 *   $spamService->check('registration', [
 *       'ip' => $request->ip(),
 *       'email' => $email,
 *       'request' => $request
 *   ]);
 * 
 *   $spamService->check('post', [
 *       'user_id' => $userId,
 *       'request' => $request
 *   ]);
 */
class SpamDetectionService
{
    /**
     * Legitimate email domains that should be trusted
     */
    protected const LEGITIMATE_DOMAINS = [
        'gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com',
        'protonmail.com', 'mail.com', 'aol.com', 'zoho.com'
    ];

    /**
     * Spam detection configurations for different action types
     */
    protected const SPAM_CONFIGS = [
        'registration' => [
            'max_attempts' => 4,
            'time_window' => 10, // minutes
            'block_duration' => 60, // minutes
            'factors' => ['ip', 'email_domain', 'fingerprint', 'combined'],
        ],
        'post' => [
            'max_attempts' => 4,
            'time_window' => 3, // minutes
            'block_duration' => 30, // minutes
            'factors' => ['user_id', 'ip', 'fingerprint'],
        ],
        'comment' => [
            'max_attempts' => 10,
            'time_window' => 5, // minutes
            'block_duration' => 15, // minutes
            'factors' => ['user_id', 'ip'],
        ],
        'invitation' => [
            'max_attempts' => 20,
            'time_window' => 10, // minutes
            'block_duration' => 30, // minutes
            'factors' => ['user_id', 'ip'],
        ],
    ];

    /**
     * Main method to check spam for any action type
     * 
     * @param string $actionType Action type (registration, post, comment, etc.)
     * @param array $params Parameters: ip, email, user_id, request
     * @return void
     * @throws \Exception
     */
    public function check(string $actionType, array $params = []): void
    {
        $config = $this->getConfig($actionType);
        $factors = $config['factors'] ?? [];

        // Check each enabled factor
        foreach ($factors as $factor) {
            switch ($factor) {
                case 'ip':
                    if (isset($params['ip']) && $params['ip']) {
                        $this->checkByFactor('ip', $params['ip'], $actionType, $config);
                    }
                    break;

                case 'email_domain':
                    if (isset($params['email']) && $params['email']) {
                        $domain = $this->extractEmailDomain($params['email']);
                        $this->checkByFactor('email_domain', $domain, $actionType, $config, true);
                    }
                    break;

                case 'fingerprint':
                    if (isset($params['request']) && $params['request']) {
                        $fingerprint = $this->getFingerprint($params['request']);
                        $this->checkByFactor('fingerprint', $fingerprint, $actionType, $config);
                    }
                    break;

                case 'user_id':
                    if (isset($params['user_id']) && $params['user_id']) {
                        $this->checkByFactor('user_id', $params['user_id'], $actionType, $config);
                    }
                    break;

                case 'combined':
                    if (isset($params['ip']) && isset($params['email'])) {
                        $domain = $this->extractEmailDomain($params['email']);
                        $this->checkCombinedPattern($params['ip'], $domain, $actionType, $config);
                    }
                    break;
            }
        }
    }

    /**
     * Check spam by a specific factor (IP, email_domain, fingerprint, user_id)
     * 
     * @param string $factorType Type of factor (ip, email_domain, fingerprint, user_id)
     * @param string|int $identifier The identifier value
     * @param string $actionType Action type
     * @param array $config Configuration for this action
     * @param bool $isDomain Whether this is an email domain check (stricter rules)
     * @return void
     * @throws \Exception
     */
    protected function checkByFactor(
        string $factorType,
        $identifier,
        string $actionType,
        array $config,
        bool $isDomain = false
    ): void {
        // Skip legitimate domains
        if ($isDomain && $this->isLegitimateDomain($identifier)) {
            return;
        }

        $blockKey = "spam_block_{$factorType}_{$actionType}_{$identifier}";
        $blockUntilKey = "spam_block_until_{$factorType}_{$actionType}_{$identifier}";
        $attemptsKey = "spam_attempts_{$factorType}_{$actionType}_{$identifier}";

        // Check if already blocked
        if (Cache::has($blockKey)) {
            $blockUntil = Cache::get($blockUntilKey);
            if ($blockUntil) {
                $remainingMinutes = max(0, Carbon::parse($blockUntil)->diffInMinutes(Carbon::now()));
                throw new \Exception($this->getBlockedMessage($factorType, $remainingMinutes));
            }
            throw new \Exception($this->getBlockedMessage($factorType));
        }

        // Get configuration with stricter rules for domains
        $maxAttempts = $isDomain ? $config['max_attempts'] * 2 : $config['max_attempts'];
        $blockDuration = $isDomain ? $config['block_duration'] * 2 : $config['block_duration'];
        $timeWindow = $config['time_window'];

        // Get and filter attempts
        $attempts = Cache::get($attemptsKey, []);
        $now = Carbon::now();
        $attempts = array_filter($attempts, function($time) use ($now, $timeWindow) {
            return Carbon::parse($time)->diffInMinutes($now) <= $timeWindow;
        });

        // Check if exceeded limit
        if (count($attempts) >= $maxAttempts) {
            $blockUntilTime = now()->addMinutes($blockDuration);
            Cache::put($blockKey, true, $blockUntilTime);
            Cache::put($blockUntilKey, $blockUntilTime->toDateTimeString(), $blockUntilTime);

            throw new \Exception($this->getExceededLimitMessage($factorType, $actionType, $blockDuration));
        }

        // Record this attempt
        $attempts[] = $now->toDateTimeString();
        Cache::put($attemptsKey, $attempts, now()->addMinutes($timeWindow + 1));
    }

    /**
     * Check combined spam pattern (IP + Email Domain)
     * 
     * @param string $ipAddress
     * @param string $emailDomain
     * @param string $actionType
     * @param array $config
     * @return void
     * @throws \Exception
     */
    protected function checkCombinedPattern(
        string $ipAddress,
        string $emailDomain,
        string $actionType,
        array $config
    ): void {
        // Skip legitimate domains
        if ($this->isLegitimateDomain($emailDomain)) {
            return;
        }

        $combinedKey = md5("{$ipAddress}_{$emailDomain}");
        $blockKey = "spam_block_combined_{$actionType}_{$combinedKey}";
        $attemptsKey = "spam_attempts_combined_{$actionType}_{$combinedKey}";

        if (Cache::has($blockKey)) {
            throw new \Exception('This IP and email domain combination has been temporarily blocked due to suspicious activity.');
        }

        // Stricter rules for combined pattern
        $maxAttempts = 3;
        $blockDuration = $config['block_duration'] * 3;
        $timeWindow = $config['time_window'];

        $attempts = Cache::get($attemptsKey, []);
        $now = Carbon::now();
        $attempts = array_filter($attempts, function($time) use ($now, $timeWindow) {
            return Carbon::parse($time)->diffInMinutes($now) <= $timeWindow;
        });

        if (count($attempts) >= $maxAttempts) {
            $blockUntilTime = now()->addMinutes($blockDuration);
            Cache::put($blockKey, true, $blockUntilTime);

            throw new \Exception("Suspicious activity detected from this IP and email domain combination. Blocked for {$blockDuration} minutes.");
        }

        $attempts[] = $now->toDateTimeString();
        Cache::put($attemptsKey, $attempts, now()->addMinutes($timeWindow + 1));
    }

    /**
     * Get configuration for an action type
     * 
     * @param string $actionType
     * @return array
     */
    protected function getConfig(string $actionType): array
    {
        return self::SPAM_CONFIGS[$actionType] ?? self::SPAM_CONFIGS['registration'];
    }

    /**
     * Check if email domain is legitimate
     * 
     * @param string $domain
     * @return bool
     */
    protected function isLegitimateDomain(string $domain): bool
    {
        return in_array(strtolower($domain), self::LEGITIMATE_DOMAINS);
    }

    /**
     * Extract email domain from email address
     * 
     * @param string $email
     * @return string
     */
    protected function extractEmailDomain(string $email): string
    {
        $parts = explode('@', $email);
        return $parts[1] ?? 'unknown';
    }

    /**
     * Generate fingerprint from request headers
     * 
     * @param Request $request
     * @return string
     */
    protected function getFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
        ];

        return md5(implode('|', array_filter($components)));
    }

    /**
     * Get blocked message for a factor type
     * 
     * @param string $factorType
     * @param int|null $remainingMinutes
     * @return string
     */
    protected function getBlockedMessage(string $factorType, ?int $remainingMinutes = null): string
    {
        $messages = [
            'ip' => $remainingMinutes 
                ? "Your IP address has been temporarily blocked. Please wait {$remainingMinutes} minutes before trying again."
                : 'Your IP address has been temporarily blocked. Please wait before trying again.',
            'email_domain' => $remainingMinutes
                ? "This email domain has been temporarily blocked. Please wait {$remainingMinutes} minutes before trying again."
                : 'This email domain has been temporarily blocked. Please wait before trying again.',
            'fingerprint' => 'This device/browser has been temporarily blocked due to suspicious activity.',
            'user_id' => $remainingMinutes
                ? "Your account has been temporarily blocked. Please wait {$remainingMinutes} minutes before trying again."
                : 'Your account has been temporarily blocked. Please wait before trying again.',
        ];

        return $messages[$factorType] ?? 'You have been temporarily blocked. Please wait before trying again.';
    }

    /**
     * Get exceeded limit message
     * 
     * @param string $factorType
     * @param string $actionType
     * @param int $blockDuration
     * @return string
     */
    protected function getExceededLimitMessage(string $factorType, string $actionType, int $blockDuration): string
    {
        $messages = [
            'ip' => "Too many {$actionType} attempts from your IP address. Your IP has been temporarily blocked for {$blockDuration} minutes.",
            'email_domain' => "Too many registration attempts from this email domain. Domain has been temporarily blocked for {$blockDuration} minutes.",
            'fingerprint' => "Too many attempts from this device. Device has been temporarily blocked for {$blockDuration} minutes.",
            'user_id' => "Too many {$actionType} attempts. Your account has been temporarily blocked for {$blockDuration} minutes.",
        ];

        return $messages[$factorType] ?? "Too many attempts. You have been temporarily blocked for {$blockDuration} minutes.";
    }

    // ========== Convenience Methods ==========

    /**
     * Check spam for user registration
     * 
     * @param string $ipAddress
     * @param string $email
     * @param Request|null $request
     * @return void
     * @throws \Exception
     */
    public function checkRegistrationSpam(string $ipAddress, string $email, ?Request $request = null): void
    {
        $this->check('registration', [
            'ip' => $ipAddress,
            'email' => $email,
            'request' => $request,
        ]);
    }

    /**
     * Check spam for post creation
     * 
     * @param int $userId
     * @param Request|null $request
     * @return void
     * @throws \Exception
     */
    public function checkPostSpam(int $userId, ?Request $request = null): void
    {
        $this->check('post', [
            'user_id' => $userId,
            'ip' => $request ? $request->ip() : null,
            'request' => $request,
        ]);
    }

    /**
     * Check spam for comment creation
     * 
     * @param int $userId
     * @param Request|null $request
     * @return void
     * @throws \Exception
     */
    public function checkCommentSpam(int $userId, ?Request $request = null): void
    {
        $this->check('comment', [
            'user_id' => $userId,
            'ip' => $request ? $request->ip() : null,
        ]);
    }

    /**
     * Check spam for invitation sending
     * 
     * @param int $userId
     * @param Request|null $request
     * @return void
     * @throws \Exception
     */
    public function checkInvitationSpam(int $userId, ?Request $request = null): void
    {
        $this->check('invitation', [
            'user_id' => $userId,
            'ip' => $request ? $request->ip() : null,
        ]);
    }
}
