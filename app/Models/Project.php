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
}
