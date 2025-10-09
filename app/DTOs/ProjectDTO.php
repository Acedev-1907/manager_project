<?php

namespace App\DTOs;

/**
 * Project Data Transfer Object
 * 
 * Sử dụng DTO pattern để transfer data giữa các layers
 * Giúp code dễ maintain và type-safe hơn
 */
class ProjectDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $content,
        public readonly ?int $creatorId,
        public readonly array $members = [],
        public readonly ?string $slug = null,
        public readonly ?int $status = null
    ) {}

    /**
     * Tạo DTO từ array request
     * 
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'],
            startDate: $data['startDate'],
            endDate: $data['endDate'],
            content: $data['content'] ?? null,
            creatorId: $data['creator_id'] ?? null,
            members: $data['members'] ?? [],
            slug: $data['slug'] ?? null,
            status: $data['status'] ?? null
        );
    }

    /**
     * Convert DTO thành array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'content' => $this->content,
            'creator_id' => $this->creatorId,
            'members' => $this->members,
            'slug' => $this->slug,
            'status' => $this->status,
        ], fn($value) => $value !== null);
    }

    /**
     * Validate dữ liệu
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->name) 
            && !empty($this->startDate) 
            && !empty($this->endDate);
    }
}

