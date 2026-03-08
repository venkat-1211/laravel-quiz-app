<?php

namespace App\DTOs;

use App\Models\Category;

class CategoryDTO
{
    public ?int $id;
    public string $name;
    public string $slug;
    public ?string $description;
    public ?string $icon;
    public bool $is_active;
    public int $order;
    public ?int $parent_id;
    public ?array $children;
    public ?int $quizzes_count;
    public ?string $created_at;
    public ?string $updated_at;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'];
        $this->slug = $data['slug'];
        $this->description = $data['description'] ?? null;
        $this->icon = $data['icon'] ?? null;
        $this->is_active = $data['is_active'] ?? true;
        $this->order = $data['order'] ?? 0;
        $this->parent_id = $data['parent_id'] ?? null;
        $this->children = $data['children'] ?? null;
        $this->quizzes_count = $data['quizzes_count'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    public static function fromModel(Category $category): self
    {
        return new self([
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'icon' => $category->icon,
            'is_active' => $category->is_active,
            'order' => $category->order,
            'parent_id' => $category->parent_id,
            'quizzes_count' => $category->quizzes_count ?? $category->quizzes()->count(),
            'created_at' => $category->created_at?->toDateTimeString(),
            'updated_at' => $category->updated_at?->toDateTimeString(),
        ]);
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'is_active' => $this->is_active,
            'order' => $this->order,
            'parent_id' => $this->parent_id,
            'children' => $this->children,
            'quizzes_count' => $this->quizzes_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}