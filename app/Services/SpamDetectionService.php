<?php

namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\SpamBlock;
use App\Models\SpamAttempt;

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

        $identifierStr = (string) $identifier;

        // Check if already blocked (active block only)
        $block = SpamBlock::where('factor_type', $factorType)
            ->where('action_type', $actionType)
            ->where('identifier', $identifierStr)
            ->active()
            ->first();

        if ($block) {
            $remainingMinutes = $block->getRemainingMinutes();
            throw new \Exception($this->getBlockedMessage($factorType, $remainingMinutes));
        }

        // Get configuration with stricter rules for domains
        $maxAttempts = $isDomain ? $config['max_attempts'] * 2 : $config['max_attempts'];
        $blockDuration = $isDomain ? $config['block_duration'] * 2 : $config['block_duration'];
        $timeWindow = $config['time_window'];

        // Count recent attempts from database
        $attemptCount = SpamAttempt::countRecentAttempts(
            $factorType,
            $actionType,
            $identifierStr,
            $timeWindow
        );

        // Check if exceeded limit
        if ($attemptCount >= $maxAttempts) {
            // Create block in database
            SpamBlock::findOrCreateBlock(
                $factorType,
                $actionType,
                $identifierStr,
                $blockDuration,
                $attemptCount
            );

            throw new \Exception($this->getExceededLimitMessage($factorType, $actionType, $blockDuration));
        }

        // Record this attempt in database
        SpamAttempt::recordAttempt($factorType, $actionType, $identifierStr);
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
        $factorType = 'combined';

        // Check if already blocked
        $block = SpamBlock::where('factor_type', $factorType)
            ->where('action_type', $actionType)
            ->where('identifier', $combinedKey)
            ->active()
            ->first();

        if ($block) {
            throw new \Exception('This IP and email domain combination has been temporarily blocked due to suspicious activity.');
        }

        // Stricter rules for combined pattern
        $maxAttempts = 3;
        $blockDuration = $config['block_duration'] * 3;
        $timeWindow = $config['time_window'];

        // Count recent attempts
        $attemptCount = SpamAttempt::countRecentAttempts(
            $factorType,
            $actionType,
            $combinedKey,
            $timeWindow
        );

        if ($attemptCount >= $maxAttempts) {
            // Create block in database
            SpamBlock::findOrCreateBlock(
                $factorType,
                $actionType,
                $combinedKey,
                $blockDuration,
                $attemptCount
            );

            throw new \Exception("Suspicious activity detected from this IP and email domain combination. Blocked for {$blockDuration} minutes.");
        }

        // Record this attempt
        SpamAttempt::recordAttempt($factorType, $actionType, $combinedKey);
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

    // ========== Unblock Methods ==========

    /**
     * Unblock a specific identifier (IP, User ID, Email Domain, Fingerprint)
     * 
     * @param string $factorType Type of factor (ip, email_domain, fingerprint, user_id, combined)
     * @param string|int $identifier The identifier value (IP address, user ID, domain, fingerprint, combined key)
     * @param string $actionType Action type (registration, post, comment, invitation)
     * @return bool True if unblocked, false if not found
     */
    public function unblock(string $factorType, $identifier, string $actionType): bool
    {
        // For combined, identifier is already the hash
        if ($factorType !== 'combined') {
            $identifier = (string) $identifier;
        }

        return SpamBlock::removeBlock($factorType, $actionType, $identifier);
    }

    /**
     * Unblock user by User ID for all action types
     * 
     * @param int $userId
     * @return array List of action types that were unblocked
     */
    public function unblockUser(int $userId): array
    {
        $actionTypes = ['post', 'comment', 'invitation'];
        $unblocked = [];

        foreach ($actionTypes as $actionType) {
            if ($this->unblock('user_id', $userId, $actionType)) {
                $unblocked[] = $actionType;
            }
        }

        return $unblocked;
    }

    /**
     * Unblock IP address for all action types
     * 
     * @param string $ipAddress
     * @return array List of action types that were unblocked
     */
    public function unblockIp(string $ipAddress): array
    {
        $actionTypes = ['registration', 'post', 'comment', 'invitation'];
        $unblocked = [];

        foreach ($actionTypes as $actionType) {
            if ($this->unblock('ip', $ipAddress, $actionType)) {
                $unblocked[] = $actionType;
            }
        }

        return $unblocked;
    }

    /**
     * Unblock email domain for registration
     * 
     * @param string $emailDomain
     * @return bool
     */
    public function unblockEmailDomain(string $emailDomain): bool
    {
        return $this->unblock('email_domain', $emailDomain, 'registration');
    }

    /**
     * Unblock combined pattern (IP + Email Domain)
     * 
     * @param string $ipAddress
     * @param string $emailDomain
     * @return bool
     */
    public function unblockCombined(string $ipAddress, string $emailDomain): bool
    {
        $combinedKey = md5("{$ipAddress}_{$emailDomain}");
        return SpamBlock::removeBlock('combined', 'registration', $combinedKey);
    }

    /**
     * Check if an identifier is currently blocked
     * 
     * @param string $factorType Type of factor (ip, email_domain, fingerprint, user_id, combined)
     * @param string|int $identifier The identifier value
     * @param string $actionType Action type
     * @return array|null Block information or null if not blocked
     */
    public function getBlockStatus(string $factorType, $identifier, string $actionType): ?array
    {
        // For combined, identifier is already the hash
        if ($factorType !== 'combined') {
            $identifier = (string) $identifier;
        }

        $block = SpamBlock::where('factor_type', $factorType)
            ->where('action_type', $actionType)
            ->where('identifier', $identifier)
            ->active()
            ->first();

        if (!$block) {
            return null;
        }

        return [
            'blocked' => true,
            'block_until' => $block->blocked_until?->toDateTimeString(),
            'remaining_minutes' => $block->getRemainingMinutes(),
            'attempt_count' => $block->attempt_count,
            'factor_type' => $block->factor_type,
            'identifier' => $block->identifier,
            'action_type' => $block->action_type,
            'created_at' => $block->created_at->toDateTimeString(),
        ];
    }

    /**
     * Clear all expired spam blocks (cleanup)
     * 
     * @return int Number of blocks deleted
     */
    public function cleanupExpiredBlocks(): int
    {
        return SpamBlock::cleanupExpired();
    }

    /**
     * Clear all spam blocks and attempts (use with caution - admin only)
     * 
     * @return array Number of records deleted
     */
    public function clearAllBlocks(): array
    {
        $blocksDeleted = SpamBlock::query()->delete();
        $attemptsDeleted = SpamAttempt::query()->delete();

        return [
            'blocks' => $blocksDeleted,
            'attempts' => $attemptsDeleted,
        ];
    }
}
