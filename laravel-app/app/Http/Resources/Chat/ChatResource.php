<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        if ($this->type === 'personal') {
            $otherMember = $this->members->where('user_id', '<>', $user->id)->first();
            if ($otherMember) {
                $this->name = $otherMember->user->name;
                $this->avatar = $otherMember->user->profile_image;
                $this->avatar_thumbnail = $otherMember->user->profile_thumbnail;
            }
        }
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'avatar_thumbnail' => $this->avatar_thumbnail,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'messages' => $this->whenLoaded('messages', fn() => ChatMessageResource::collection($this->messages)),
        ];
    }
}
