<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\ChatMember;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChatMemberSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chats = Chat::doesntHave('members')->get();

        foreach ($chats as $chat) {
            if ($chat->type === 'personal') {
                $otherUser = User::where('id', '<>', $chat->creator_id)
                    ->whereDoesntHave('chats', function ($query) use ($chat) {
                        $query->where('chat_id', $chat->id);
                    })
                    ->inRandomOrder()
                    ->first();

                ChatMember::create([
                    'chat_id' => $chat->id,
                    'user_id' => $chat->creator_id,
                    'role' => 'member',
                ]);
                ChatMember::create([
                    'chat_id' => $chat->id,
                    'user_id' => $otherUser->id,
                    'role' => 'member',
                ]);
            } else {
                $users = User::where('id', '<>', $chat->creator_id)
                    ->whereDoesntHave('chats', function ($query) use ($chat) {
                        $query->where('chat_id', $chat->id);
                    })
                    ->inRandomOrder()
                    ->take(5)
                    ->get();

                ChatMember::create([
                    'chat_id' => $chat->id,
                    'user_id' => $chat->creator_id,
                    'role' => 'admin',
                ]);

                foreach ($users as $user) {
                    if ($user->id === $chat->creator_id) {
                        continue;
                    }
                    ChatMember::create([
                        'chat_id' => $chat->id,
                        'user_id' => $user->id,
                        'role' => 'member',
                    ]);
                }
            }
        }
    }
}
