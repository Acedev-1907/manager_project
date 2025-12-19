<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Lấy user còn lại trong cuộc hội thoại
     */
    public function otherUser($userId)
    {
        if ((int) $this->user_one_id === (int) $userId) {
            return $this->userTwo;
        }

        return $this->userOne;
    }
}


