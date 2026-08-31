<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        // Create 5 personal chats
        for ($i = 0; $i < 5; $i++) {
            Chat::create([
                'creator_id' => $users->random()->id,
                'type' => 'personal',
                'name' => null,
                'description' => null,
                'avatar' => null,
            ]);
        }

        // Create 5 group chats
        for ($i = 0; $i < 5; $i++) {
            Chat::create([
                'creator_id' => $users->random()->id,
                'type' => 'group',
                'name' => 'Group Chat ' . ($i + 1),
                'description' => 'Description for group chat ' . ($i + 1),
                'avatar' => null,
            ]);
        }
    }
}
