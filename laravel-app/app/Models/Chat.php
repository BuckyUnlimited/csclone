<?php

namespace App\Models;

use App\Services\ImageClassService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['creator_id', 'type', 'name', 'description', 'avatar'])]
class Chat extends Model
{
    use SoftDeletes;

    protected $table = 'chats';
    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'type' => 'string',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChatMember::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    // profile image related methods and attributes
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: function () {
                $imageClass = ImageClassService::forChatModel();
                $imagePath = $this->getRawOriginal('avatar');
                return $imageClass->fullUrl($imagePath);
            },
        );
    }

    protected function avatarThumbnail(): Attribute
    {
        return Attribute::make(
            get: function () {
                $imageClass = ImageClassService::forChatModel();
                $thumbnailPath = $imageClass->thumbnailPath($this->getRawOriginal('avatar'));
                return $imageClass->fullUrl($thumbnailPath);
            },
        );
    }
    // end profile image related methods and attributes
}
