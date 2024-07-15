<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'session_id',
        'conversation',
        'intent_state'
    ];

    protected $casts = [
        'conversation' => 'array',
    ];

    /**
     * Get the user that owns the chat interaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function addToConversation($message, $isUser = true)
    {
        $conversation = $this->conversation ?? [];
        $conversation[] = [
            'type' => $isUser ? 'user' : 'bot',
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->conversation = $conversation;
        $this->save();
    }
    public function getLastUserMessage()
    {
        $conversation = $this->conversation ?? [];
        $userMessages = array_filter($conversation, function ($item) {
            return $item['type'] === 'user';
        });
        return end($userMessages)['message'] ?? null;
    }

    public function getLastBotMessage()
    {
        $conversation = $this->conversation ?? [];
        $botMessages = array_filter($conversation, function ($item) {
            return $item['type'] === 'bot';
        });
        return end($botMessages)['message'] ?? null;
    }
}
