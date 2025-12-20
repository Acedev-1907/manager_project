<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SpamDetectionService;

/**
 * Artisan command to unblock spam blocked users/IPs
 * 
 * Usage:
 *   php artisan spam:unblock user 123
 *   php artisan spam:unblock ip 192.168.1.1
 *   php artisan spam:unblock email-domain example.com
 *   php artisan spam:unblock user 123 post
 */
class UnblockSpam extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spam:unblock 
                            {type : Type to unblock (user, ip, email-domain, combined)}
                            {identifier : Identifier value (user ID, IP address, email domain, or "ip|domain" for combined)}
                            {action? : Optional action type (post, comment, registration, invitation)}
                            {--all : Unblock for all action types}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unblock a user, IP address, email domain, or combined pattern from spam blocks';

    /**
     * Execute the console command.
     */
    public function handle(SpamDetectionService $spamService)
    {
        $type = $this->argument('type');
        $identifier = $this->argument('identifier');
        $action = $this->argument('action');
        $all = $this->option('all');

        try {
            switch ($type) {
                case 'user':
                    $userId = (int) $identifier;
                    if ($userId <= 0) {
                        $this->error('Invalid user ID');
                        return 1;
                    }

                    if ($all || !$action) {
                        $unblocked = $spamService->unblockUser($userId);
                        if (!empty($unblocked)) {
                            $this->info("User ID {$userId} unblocked for: " . implode(', ', $unblocked));
                        } else {
                            $this->warn("User ID {$userId} is not currently blocked");
                        }
                    } else {
                        if ($spamService->unblock('user_id', $userId, $action)) {
                            $this->info("User ID {$userId} unblocked for action: {$action}");
                        } else {
                            $this->warn("User ID {$userId} is not blocked for action: {$action}");
                        }
                    }
                    break;

                case 'ip':
                    if ($all || !$action) {
                        $unblocked = $spamService->unblockIp($identifier);
                        if (!empty($unblocked)) {
                            $this->info("IP {$identifier} unblocked for: " . implode(', ', $unblocked));
                        } else {
                            $this->warn("IP {$identifier} is not currently blocked");
                        }
                    } else {
                        if ($spamService->unblock('ip', $identifier, $action)) {
                            $this->info("IP {$identifier} unblocked for action: {$action}");
                        } else {
                            $this->warn("IP {$identifier} is not blocked for action: {$action}");
                        }
                    }
                    break;

                case 'email-domain':
                    if ($spamService->unblockEmailDomain($identifier)) {
                        $this->info("Email domain {$identifier} unblocked");
                    } else {
                        $this->warn("Email domain {$identifier} is not currently blocked");
                    }
                    break;

                case 'combined':
                    $parts = explode('|', $identifier);
                    if (count($parts) !== 2) {
                        $this->error('Combined format must be: ip|domain (e.g., 192.168.1.1|example.com)');
                        return 1;
                    }
                    [$ip, $domain] = $parts;
                    if ($spamService->unblockCombined($ip, $domain)) {
                        $this->info("Combined pattern (IP: {$ip}, Domain: {$domain}) unblocked");
                    } else {
                        $this->warn("Combined pattern (IP: {$ip}, Domain: {$domain}) is not currently blocked");
                    }
                    break;

                default:
                    $this->error("Unknown type: {$type}. Valid types: user, ip, email-domain, combined");
                    return 1;
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}

