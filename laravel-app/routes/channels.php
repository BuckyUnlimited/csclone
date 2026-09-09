<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('ChatEvent.{userId}', function ($user, $userId) {
    // Check if the user is a participant of the chat
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('MessageEvent.{chatId}', function ($user, $chatId) {
    // Check if the user is a participant of the chat
    return $user->chats()->where('chats.id', $chatId)->exists();
});
