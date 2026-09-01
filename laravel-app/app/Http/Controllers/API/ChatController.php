<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\CreateGroupChatRequest;
use App\Http\Requests\Chat\CreatePersonalChatRequest;
use App\Http\Requests\Chat\DeleteChatRequest;
use App\Http\Requests\Chat\GetChatsRequest;
use App\Http\Requests\Chat\GetChatUsersRequest;
use App\Http\Requests\Chat\LeaveGroupChatRequest;
use App\Http\Requests\Chat\ReadChatRequest;
use App\Http\Requests\Chat\UpdateGroupChatRequest;
use App\Http\Resources\Chat\ChatResource;
use App\Http\Resources\Chat\ChatUserResource;
use App\Models\Chat;
use App\Models\ChatMember;
use App\Models\User;
use App\Services\ImageClassService;
use Illuminate\Support\Facades\DB;
use Exception;

class ChatController extends Controller
{
    public function getChatUsers(GetChatUsersRequest $request)
    {
        $user = $request->user();
        $keyword = $request->input('keyword', null);
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);

        $users = User::whereNot('id', $user->id)
            ->whereDoesntHave('chats', function ($query) use ($user) {
                $query->where('type', 'personal')
                    ->whereHas('members', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    });
            })
            ->when($keyword, function ($query, $keyword) {
                $query
                    ->where(function ($query) use ($keyword) {
                        $query->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
            })->paginate($perPage, ['*'], 'page', $page);

        return response([
            'users' => ChatUserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ], 200);
    }

    public function getChats(GetChatsRequest $request)
    {
        $user = $request->user();
        $keyword = $request->input('keyword', null);
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);

        $chats = Chat::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->when($keyword, function ($query, $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('members.user', function ($query) use ($keyword) {
                            $query->where('name', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%");
                        });
                });
            })

            // Use LEFT JOIN to get latest message without subquery
            ->selectRaw('chats.*,
                (SELECT MAX(created_at) FROM chat_messages WHERE chat_id = chats.id) as latest_message_at')
            ->orderByDesc('latest_message_at')
            ->orderBy('created_at', 'desc')

            // load messages with limit 25 and order by created_at desc (newest first)
            ->with([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'desc')
                        ->with('creator');
                },
                'members.user',
            ])
            ->paginate($perPage, ['*'], 'page', $page);

        return response([
            'chats' => ChatResource::collection($chats),
            'meta' => [
                'current_page' => $chats->currentPage(),
                'last_page' => $chats->lastPage(),
                'per_page' => $chats->perPage(),
                'total' => $chats->total(),
            ],
        ], 200);
    }

    public function createPersonalChat(CreatePersonalChatRequest $request)
    {
        $user = $request->user();
        $otherUserId = $request->user_id;

        // Check if chat already exists between these two users
        $existingChat = Chat::where('type', 'personal')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereHas('members', function ($query) use ($otherUserId) {
                $query->where('user_id', $otherUserId);
            })
            ->first();

        if ($existingChat) {
            return response([
                'message' => 'Personal chat already exists',
                'chat' => new ChatResource($existingChat->load([
                    'messages' => function ($query) {
                        $query->limit(25)
                            ->orderBy('created_at', 'desc')
                            ->with('creator');
                    },
                    'members.user'
                ]))
            ], 200);
        }

        try {
            DB::beginTransaction();

            $chat = Chat::create([
                'creator_id' => $user->id,
                'type' => 'personal',
            ]);

            // Add both members
            ChatMember::create([
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'role' => 'member',
            ]);

            ChatMember::create([
                'chat_id' => $chat->id,
                'user_id' => $otherUserId,
                'role' => 'member',
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'message' => 'Failed to create chat'
            ], 500);
        }

        return response([
            'message' => 'Chat created.',
            'chat' => new ChatResource($chat->load([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'desc')
                        ->with('creator');
                },
                'members.user'
            ]))
        ], 201);
    }

    public function createGroupChat(CreateGroupChatRequest $request)
    {
        $imageClass = ImageClassService::forChatModel();
        $user = $request->user();
        $avatarPath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('avatar')) {
                $avatarPath = $imageClass->store($request->file('avatar'));
            }

            $chat = Chat::create([
                'creator_id' => $user->id,
                'type' => 'group',
                'name' => $request->name,
                'description' => $request->description,
                'avatar' => $avatarPath,
            ]);

            // Add creator as admin
            ChatMember::create([
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'role' => 'admin',
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $imageClass->delete($avatarPath);
            return response([
                'message' => 'Failed to create chat'
            ], 500);
        }

        return response([
            'message' => 'Chat created.',
            'chat' => new ChatResource($chat->load([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'desc')
                        ->with('creator');
                },
                'members.user'
            ]))
        ], 201);
    }

    public function readChat(ReadChatRequest $request)
    {
        $user = $request->user();
        $chatId = $request->route('chatId');

        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'desc')
                        ->with('creator');
                },
                'members.user',
            ])
            ->firstOrFail();

        return response([
            'chat' => new ChatResource($chat)
        ], 200);
    }

    public function deleteChat(DeleteChatRequest $request)
    {
        $user = $request->user();
        $chatId = $request->route('chatId');

        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Only group admin can delete
        $currentMember = $chat->members()->where('user_id', $user->id)->first();
        if ($chat->type === 'group' && $currentMember->role !== 'admin') {
            return response([
                'message' => 'Unauthorized to delete this chat'
            ], 403);
        }

        try {
            DB::beginTransaction();
            $chat->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'message' => 'Failed to delete chat'
            ], 500);
        }

        return response([
            'message' => 'Chat deleted.'
        ], 200);
    }

    public function updateGroupChat(UpdateGroupChatRequest $request)
    {
        $imageClass = ImageClassService::forChatModel();
        $user = $request->user();
        $chatId = $request->route('chatId');

        $chat = Chat::where('id', $chatId)
            ->where('type', 'group')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Only creator or admin can update
        $currentMember = $chat->members()->where('user_id', $user->id)->first();
        if ($currentMember->role !== 'admin') {
            return response([
                'message' => 'Unauthorized to update this chat'
            ], 403);
        }

        $oldAvatarPath = $chat->getRawOriginal('avatar');
        $newAvatarPath = null;
        $shouldDeleteOldAvatar = false;

        try {
            DB::beginTransaction();

            $chat->name = $request->name;
            $chat->description = $request->description;

            // Handle avatar update logic
            if ($request->has('avatar')) {
                if ($request->hasFile('avatar')) {
                    // Avatar present with file - update and delete old
                    $newAvatarPath = $imageClass->store($request->file('avatar'));
                    $chat->avatar = $newAvatarPath;
                    $shouldDeleteOldAvatar = true;
                } else {
                    // Avatar present but null - delete avatar
                    $chat->avatar = null;
                    $shouldDeleteOldAvatar = true;
                }
            }
            // If avatar not present in request - do nothing (keep existing)

            $chat->save();

            if ($shouldDeleteOldAvatar && $oldAvatarPath) {
                $imageClass->delete($oldAvatarPath);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            if ($newAvatarPath) {
                $imageClass->delete($newAvatarPath);
            }
            return response([
                'message' => 'Failed to update chat'
            ], 500);
        }

        return response([
            'message' => 'Chat updated.',
            'chat' => new ChatResource($chat->load([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'desc')
                        ->with('creator');
                },
                'members.user'
            ]))
        ], 200);
    }

    public function leaveGroupChat(LeaveGroupChatRequest $request)
    {
        $user = $request->user();
        $chatId = $request->route('chatId');

        $chat = Chat::where('id', $chatId)
            ->where('type', 'group')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // If no members left, delete the chat
            $remainingMembers = $chat->members()->count();
            if ($remainingMembers === 1) {
                $chat->delete();
            } else {
                // transfer admin role if the leaving member is an admin
                $currentMember = $chat->members()->where('user_id', $user->id)->first();
                if ($currentMember && $currentMember->role === 'admin') {
                    // Update another member to admin using update query
                    ChatMember::where('chat_id', $chat->id)
                        ->where('user_id', '!=', $user->id)
                        ->limit(1)
                        ->update(['role' => 'admin']);
                }
                // Remove the leaving member from the chat
                $chat->members()->where('user_id', $user->id)->delete();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'message' => 'Failed to leave chat'
            ], 500);
        }

        return response([
            'message' => 'Left chat successfully.'
        ], 200);
    }
}
