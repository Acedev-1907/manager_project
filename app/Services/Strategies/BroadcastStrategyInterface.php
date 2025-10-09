<?php

namespace App\Services\Strategies;

use App\Models\Project;

/**
 * Broadcast Strategy Interface
 * 
 * Strategy Pattern cho việc broadcast events
 */
interface BroadcastStrategyInterface
{
    /**
     * Execute broadcast
     * 
     * @param Project $project
     * @param array $data
     * @return void
     */
    public function execute(Project $project, array $data): void;
}

