<?php

namespace App\Services\Strategies;

use App\Models\Project;
use App\Events\UserProjectCountUpdated;
use App\Events\NewProjectForMembers;
use App\Notifications\NewProjectAssigned;
use App\Models\User;

/**
 * Project Created Broadcast Strategy
 * 
 * Xử lý broadcast khi project được tạo
 */
class ProjectCreatedBroadcast implements BroadcastStrategyInterface
{
    /**
     * Execute broadcast
     * 
     * @param Project $project
     * @param array $data
     * @return void
     */
    public function execute(Project $project, array $data): void
    {
        $allMembers = $data['members'] ?? [];
        $projectCount = $data['projectCount'] ?? [];

        // Load project relations
        $project->load([
            'creator',
            'users' => function ($q) {
                $q->select('users.id', 'users.name', 'users.avatar');
            }
        ]);

        // Broadcast count updates
        foreach ($allMembers as $memberId) {
            $count = $projectCount[$memberId] ?? 0;
            UserProjectCountUpdated::dispatch($memberId, $count);
        }

        // Broadcast project creation
        foreach ($allMembers as $memberId) {
            broadcast(new NewProjectForMembers($project, $memberId));
            
            // Send notification
            $member = User::find($memberId);
            if ($member) {
                $member->notify(new NewProjectAssigned($project, $memberId));
            }
        }
    }
}

