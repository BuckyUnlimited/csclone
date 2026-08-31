<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatMember;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChatMessageSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sampleMessages = [
            'Hello! How are you doing today?',
            'That sounds great! Tell me more.',
            'I completely agree with you on that.',
            'Thanks for sharing this information.',
            'This is really interesting and insightful.',
            'I had a great time chatting with you.',
            'Looking forward to hearing from you soon.',
            'Can you help me with this problem?',
            'I really appreciate your help and support.',
            'See you later, take care!',
            'This project is coming along nicely.',
            'Great work on completing that task!',
            'Let me know if you need anything else.',
            'I will send you the details tomorrow.',
            'Thanks for the update, very helpful!',
            'What do you think about this idea?',
            'I am excited about this new opportunity.',
            'Let us schedule a meeting to discuss.',
            'Your suggestion is very valuable to us.',
            'I look forward to collaborating with you.',
        ];

        $chats = Chat::doesntHave('messages')->get();

        foreach ($chats as $chat) {
            if (rand(0, 100) < 30) {
                continue;
            }

            $members = ChatMember::where('chat_id', $chat->id)->pluck('user_id')->toArray();

            // Create 10 text messages per chat
            $seen = rand(0, 1) === 1;

            for ($i = 0; $i < 10; $i++) {
                $creatorId = $members[array_rand($members)];

                ChatMessage::create([
                    'chat_id' => $chat->id,
                    'creator_id' => $creatorId,
                    'type' => 'text',
                    'content' => $sampleMessages[array_rand($sampleMessages)],
                    'file_name' => null,
                    'file_path' => null,
                    'mime_type' => null,
                    'seen_at' => $seen ? now() : null,
                ]);
            }
        }
    }
}
