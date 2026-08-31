<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['chat_id', 'creator_id', 'type', 'content', 'file_name', 'file_path', 'mime_type', 'seen_at'])]
class ChatMessage extends Model
{
    use SoftDeletes;

    protected $table = 'chat_messages';
    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'seen_at' => 'datetime',
        ];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
