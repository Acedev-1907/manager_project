<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskMember extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function member()
    {
        return $this->belongsTo(Member::class, 'memberId');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'memberId', 'id');
    }
}
