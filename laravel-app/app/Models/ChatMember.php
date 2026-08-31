<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['chat_id', 'user_id', 'role', 'joined_at'])]
class ChatMember extends Model
{
    protected $table = 'chat_members';
    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'role' => 'string',
            'joined_at' => 'datetime',
        ];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
