<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'sender_type',
        'attachment_url',
        'attachment_type',
        'attachment_name',
        'metadata',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'sender_id' => 'integer',
            'receiver_id' => 'integer',
            'metadata' => 'array',
        ];
    }
}
