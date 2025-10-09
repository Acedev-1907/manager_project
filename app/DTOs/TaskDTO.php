<?php

namespace App\DTOs;

/**
 * Task Data Transfer Object
 * 
 * DTO cho Task entity
 */
class TaskDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $projectId,
        public readonly ?int $status,
        public readonly ?int $priority,
        public readonly ?string $dueDate,
        public readonly array $members = []
    ) {}

    /**
     * Tạo DTO từ array
     * 
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            title: $data['title'],
            description: $data['description'] ?? null,
            projectId: $data['projectId'],
            status: $data['status'] ?? null,
            priority: $data['priority'] ?? null,
            dueDate: $data['dueDate'] ?? null,
            members: $data['members'] ?? []
        );
    }

    /**
     * Convert thành array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'projectId' => $this->projectId,
            'status' => $this->status,
            'priority' => $this->priority,
            'dueDate' => $this->dueDate,
            'members' => $this->members,
        ], fn($value) => $value !== null);
    }
}

