<?php

use App\Domains\Chat\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{user_id}.chat.{chat_id}', function ($user_id, $chat_id) {
    \Illuminate\Support\Facades\Log::info($user_id);
    \Illuminate\Support\Facades\Log::info($chat_id);
    return Chat::where('id', $chat_id)
        ->where('user_id', $user_id)
        ->exists();
});
