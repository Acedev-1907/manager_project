<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Project Model
 * 
 * Represents a project with its associated tasks, members, and board columns.
 * Handles project lifecycle and column management.
 */
class Project extends Model
{
  use HasFactory;

  // Project status constants
  const NOT_STARTED = 0;
  const PENDING = 1;
  const COMPLETED = 1;

  // Default column colors
  const DEFAULT_COLUMN_COLOR = '#3b82f6';
  const DEFAULT_COLUMN_ICON = 'fas fa-columns';

  protected $guarded = [];

  protected $casts = [
    'board_columns' => 'array',
    'start_date' => 'date',
    'end_date' => 'date'
  ];

  /**
   * Model boot method - handle model events
   */
  protected static function booted()
  {
    static::deleting(function ($project) {
      // Detach all users from the pivot table when deleting a project
      $project->users()->detach();
    });
  }

  /**
   * Create a unique slug for the project
   * 
   * @param string $name Project name
   * @return string
   */
  public static function createSlug(string $name): string
  {
    $code = Str::random(10) . time();
    return Str::slug($name) . '-' . $code;
  }

  /**
   * Get task progress relationship
   */
  public function task_progress()
  {
    return $this->hasOne(TaskProgress::class, 'projectId');
  }

  /**
   * Get tasks relationship
   */
  public function tasks()
  {
    return $this->hasMany(Task::class, 'projectId');
  }

  /**
   * Get members relationship
   */
  public function members()
  {
    return $this->hasMany(Member::class, 'projectId');
  }

  /**
   * Get users relationship (many-to-many)
   */
  public function users()
  {
    return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id');
  }

  /**
   * Get creator relationship
   */
  public function creator()
  {
    return $this->belongsTo(User::class, 'creator_id');
  }

  /**
   * Get board columns for the project
   * Creates default columns if none exist
   * 
   * @return \Illuminate\Support\Collection
   */
  public function getBoardColumns()
  {
    if (!$this->board_columns) {
      // Create default columns if none exist
      $this->board_columns = $this->createDefaultColumns();
      $this->save();
    }

    return collect($this->board_columns)->sortBy('position');
  }

  /**
   * Create default board columns for new projects
   * 
   * @return array
   */
  public function createDefaultColumns(): array
  {
    return [
      [
        'id' => 1,
        'name' => 'Not Started',
        'key_name' => 'not-started',
        'position' => 0,
        'color' => self::DEFAULT_COLUMN_COLOR,
        'icon' => 'fas fa-circle',
        'created_at' => now(),
        'updated_at' => now()
      ],
      [
        'id' => 2,
        'name' => 'Pending',
        'key_name' => 'pending',
        'position' => 1,
        'color' => '#f59e0b',
        'icon' => 'fas fa-clock',
        'created_at' => now(),
        'updated_at' => now()
      ],
      [
        'id' => 3,
        'name' => 'Completed',
        'key_name' => 'completed',
        'position' => 2,
        'color' => '#10b981',
        'icon' => 'fas fa-check-circle',
        'created_at' => now(),
        'updated_at' => now()
      ]
    ];
  }

  /**
   * Get column status by column ID
   * 
   * @param int $columnId
   * @return int|null
   */
  public function getColumnStatus(int $columnId): ?int
  {
    $columns = $this->getBoardColumns();
    $column = $columns->firstWhere('id', $columnId);

    return $column ? $column['position'] : null;
  }

  /**
   * Check if project is completed
   * 
   * @return bool
   */
  public function isCompleted(): bool
  {
    return $this->status === self::COMPLETED;
  }

  /**
   * Mark project as completed
   * 
   * @return void
   */
  public function markAsCompleted(): void
  {
    $this->update(['status' => self::COMPLETED]);
  }

  /**
   * Mark project as active (not started)
   * 
   * @return void
   */
  public function markAsActive(): void
  {
    $this->update(['status' => self::NOT_STARTED]);
  }

  /**
   * Add a new column to the project
   * 
   * @param string $name Column name
   * @param string $color Column color (hex)
   * @param string $icon Column icon class
   * @return array|null
   */
  public function addColumn(string $name, string $color = self::DEFAULT_COLUMN_COLOR, string $icon = self::DEFAULT_COLUMN_ICON): ?array
  {
    $columns = $this->getBoardColumns();

    // Check if column name already exists
    if ($columns->where('name', $name)->count() > 0) {
      throw new \Exception("Column with name '{$name}' already exists");
    }

    // Find the next available position and ID
    $maxPosition = $columns->max('position') ?? -1;
    $maxId = $columns->max('id') ?? 0;

    $newColumn = [
      'id' => $maxId + 1,
      'name' => $name,
      'key_name' => Str::slug($name),
      'position' => $maxPosition + 1,
      'color' => $color,
      'icon' => $icon,
      'created_at' => now(),
      'updated_at' => now()
    ];

    $columns->push($newColumn);
    $this->board_columns = $columns->toArray();
    $this->save();

    return $newColumn;
  }

  /**
   * Update an existing column
   * 
   * @param int $columnId
   * @param array $data Update data
   * @return array|null
   */
  public function updateColumn(int $columnId, array $data): ?array
  {
    $columns = $this->getBoardColumns();
    $columnIndex = $columns->search(function ($column) use ($columnId) {
      return $column['id'] == $columnId;
    });

    if ($columnIndex === false) {
      return null;
    }

    // Check if new name conflicts with existing columns
    if (isset($data['name'])) {
      $existingColumn = $columns->where('name', $data['name'])->where('id', '!=', $columnId)->first();
      if ($existingColumn) {
        throw new \Exception("Column with name '{$data['name']}' already exists");
      }
    }

    // Update column data
    $columns[$columnIndex] = array_merge($columns[$columnIndex], $data, [
      'updated_at' => now()
    ]);

    $this->board_columns = $columns->toArray();
    $this->save();

    return $columns[$columnIndex];
  }

  /**
   * Delete a column from the project
   * 
   * @param int $columnId
   * @return bool
   */
  public function deleteColumn(int $columnId): bool
  {
    $columns = $this->getBoardColumns();
    $columnIndex = $columns->search(function ($column) use ($columnId) {
      return $column['id'] == $columnId;
    });

    if ($columnIndex === false) {
      return false;
    }

    // Remove the column
    $columns->forget($columnIndex);

    // Reindex positions
    $columns = $columns->values()->map(function ($column, $index) {
      $column['position'] = $index;
      return $column;
    });

    $this->board_columns = $columns->toArray();
    $this->save();

    return true;
  }

  /**
   * Update column positions based on new order
   * 
   * @param array $positions Array of column IDs in new order
   * @return bool
   */
  public function updateColumnPositions(array $positions): bool
  {
    $columns = $this->getBoardColumns();

    // Update positions based on new order
    foreach ($positions as $newPosition => $columnId) {
      $columnIndex = $columns->search(function ($column) use ($columnId) {
        return $column['id'] == $columnId;
      });

      if ($columnIndex !== false) {
        $columns[$columnIndex]['position'] = $newPosition;
      }
    }

    // Sort by new positions
    $columns = $columns->sortBy('position')->values();

    $this->board_columns = $columns->toArray();
    $this->save();

    return true;
  }
}
