<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatFeedback extends Model
{
    use HasFactory;

    protected $table = 'chat_feedbacks';

    protected $fillable = [
        'user_id',
        'message_id',
        'rating',
        'feedback_type',
        'comment',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'tags' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }
}
