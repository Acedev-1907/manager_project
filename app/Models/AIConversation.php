<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AIConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ai_conversations';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the conversation
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project associated with the conversation
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get all messages in the conversation
     */
    public function messages()
    {
        return $this->hasMany(AIMessage::class, 'ai_conversation_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get the last message in the conversation
     */
    public function lastMessage()
    {
        return $this->hasOne(AIMessage::class, 'ai_conversation_id')->latestOfMany();
    }
}
