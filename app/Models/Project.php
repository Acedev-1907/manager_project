<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Project extends Model
{
  use HasFactory;

  const NOT_STARTED = 0;

  const PEDDING = 1;

  const COMPLETED = 1;

  protected $guarded = [];

  protected static function booted()
  {
    static::deleting(function ($project) {
      // Detach all users from the pivot table when deleting a project
      $project->users()->detach();
    });
  }

  public static function createSlug($name)
  {
    $code = Str::random(10) . time();
    $slug =  Str::slug($name) . '-' . $code;
    return $slug;
  }

  public function task_progress()
  {
    return $this->hasOne(TaskProgress::class, 'projectId');
  }

  public function tasks()
  {
    return $this->hasMany(Task::class, 'projectId');
  }

  public function members()
  {
    return $this->hasMany(\App\Models\Member::class, 'projectId');
  }

  public function users()
  {
    return $this->belongsToMany(\App\Models\User::class, 'project_user', 'project_id', 'user_id');
  }

  public function creator()
  {
    return $this->belongsTo(\App\Models\User::class, 'creator_id');
  }

  protected $casts = [
    'board_columns' => 'array',
    'start_date' => 'date',
    'end_date' => 'date'
  ];

  /**
   * Lấy danh sách columns của project
   */
  public function getBoardColumns()
  {
    if (!$this->board_columns) {
      // Tạo default columns nếu chưa có
      $this->board_columns = $this->createDefaultColumns();
      $this->save();
    }

    return collect($this->board_columns)->sortBy('position');
  }

  /**
   * Tạo default columns
   */
  public function createDefaultColumns()
  {
    return [
      [
        'id' => 1,
        'name' => 'Not Started',
        'key_name' => 'not-started',
        'position' => 0,
        'color' => '#3b82f6',
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
      ]
    ];
  }

  /**
   * Lấy status của column theo ID
   */
  public function getColumnStatus($columnId)
  {
    $columns = $this->getBoardColumns();
    $column = $columns->firstWhere('id', $columnId);
    return $column ? $column['position'] : null;
  }

  /**
   * Kiểm tra xem project có completed không
   */
  public function isCompleted()
  {
    return $this->status === 'OK';
  }

  /**
   * Đánh dấu project là completed
   */
  public function markAsCompleted()
  {
    $this->status = 'OK';
    $this->save();
  }

  /**
   * Đánh dấu project là active
   */
  public function markAsActive()
  {
    $this->status = 'ACTIVE';
    $this->save();
  }

  /**
   * Thêm column mới
   */
  public function addColumn($name, $color = '#3b82f6', $icon = 'fas fa-columns')
  {
    $columns = $this->getBoardColumns();
    $maxPosition = $columns->max('position') ?? -1;
    $maxId = $columns->max('id') ?? 0;

    $newColumn = [
      'id' => $maxId + 1,
      'name' => $name,
      'key_name' => Str::slug($name) . '-' . Str::random(5),
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
   * Cập nhật column
   */
  public function updateColumn($columnId, $data)
  {
    $columns = $this->getBoardColumns();
    $column = $columns->firstWhere('id', $columnId);

    if ($column) {
      $column = array_merge($column, $data, ['updated_at' => now()]);
      $columns = $columns->map(function ($col) use ($columnId, $column) {
        return $col['id'] == $columnId ? $column : $col;
      });

      $this->board_columns = $columns->toArray();
      $this->save();

      return $column;
    }

    return null;
  }

  /**
   * Xóa column
   */
  public function deleteColumn($columnId)
  {
    $columns = $this->getBoardColumns();
    $columns = $columns->filter(function ($column) use ($columnId) {
      return $column['id'] != $columnId;
    });

    $this->board_columns = $columns->toArray();
    $this->save();

    return true;
  }

  /**
   * Cập nhật vị trí columns
   */
  public function updateColumnPositions($positions)
  {
    $columns = $this->getBoardColumns();

    foreach ($positions as $columnId => $position) {
      $columns = $columns->map(function ($column) use ($columnId, $position) {
        if ($column['id'] == $columnId) {
          $column['position'] = $position;
          $column['updated_at'] = now();
        }
        return $column;
      });
    }

    $this->board_columns = $columns->toArray();
    $this->save();
  }
}
