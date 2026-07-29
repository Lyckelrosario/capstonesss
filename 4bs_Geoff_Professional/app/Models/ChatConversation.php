<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatConversation extends Model
{
    public const STATUS_AI = 'ai';
    public const STATUS_WAITING = 'waiting_for_admin';
    public const STATUS_LIVE = 'live';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'assigned_admin_id',
        'status',
        'subject',
        'live_requested_at',
        'claimed_at',
        'closed_at',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'live_requested_at' => 'datetime',
            'claimed_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')->latestOfMany();
    }
}
