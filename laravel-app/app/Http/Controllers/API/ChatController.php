<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\AddGroupChatMemberRequest;
use App\Http\Requests\Chat\CreateGroupChatRequest;
use App\Http\Requests\Chat\CreatePersonalChatRequest;
use App\Http\Requests\Chat\DeleteChatRequest;
use App\Http\Requests\Chat\GetChatsRequest;
use App\Http\Requests\Chat\GetChatUsersRequest;
use App\Http\Requests\Chat\GetGroupChatMembersRequest;
use App\Http\Requests\Chat\LeaveGroupChatRequest;
use App\Http\Requests\Chat\ReadChatRequest;
use App\Http\Requests\Chat\RemoveGroupChatMemberRequest;
use App\Http\Requests\Chat\UpdateGroupChatRequest;
use App\Http\Resources\Chat\ChatMemberResource;
use App\Http\Resources\Chat\ChatResource;
use App\Http\Resources\Chat\ChatUserResource;
use App\Models\Chat;
use App\Models\ChatMember;
use App\Models\User;
use App\Services\ImageClassService;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Http\Requests\Chat\CreateChatMessageRequest;
use App\Http\Requests\Chat\DeleteChatMessageRequest;
use App\Http\Requests\Chat\GetChatMessagesRequest;
use App\Http\Requests\Chat\MarkAllChatMessagesAsSeenRequest;
use App\Http\Requests\Chat\UpdateChatMessageRequest;
use App\Http\Resources\Chat\ChatMessageResource;
use App\Models\ChatMessage;

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

    public function getGroupChatMembers(GetGroupChatMembersRequest $request)
    {
        $user = $request->user();
        $chatId = $request->route('chatId');

        $keyword = $request->input('keyword', null);
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);

        $chat = Chat::where('id', $chatId)
            ->where('type', 'group')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        $members = $chat->members()
            ->when($keyword, function ($query, $keyword) {
                $query->whereHas('user', function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('role', 'asc') // Admins first // admin start with a, members with m, so asc will put admin first
            ->with('user')
            ->paginate($perPage, ['*'], 'page', $page);

        return response([
            'members' => ChatMemberResource::collection($members),
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ], 200);
    }

    public function addGroupChatMember(AddGroupChatMemberRequest $request)
    {
        $user = $request->user();
        $chatId = $request->route('chatId');

        $chat = Chat::where('id', $chatId)
            ->where('type', 'group')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Only group admin can add members
        $currentMember = $chat->members()->where('user_id', $user->id)->first();
        if ($currentMember->role !== 'admin') {
            return response([
                'message' => 'Unauthorized to add members to this chat'
            ], 403);
        }

        if ($request->user_id == $user->id) {
            return response([
                'message' => 'You cannot add yourself to the chat. You are already a member.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Add new member
            $member = ChatMember::firstOrCreate([
                'chat_id' => $chat->id,
                'user_id' => $request->user_id,
            ], [
                'role' => 'member',
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'message' => 'Failed to add member to chat'
            ], 500);
        }
        return response([
            'message' => 'Member added successfully',
            'member' => new ChatMemberResource($member->load('user'))
        ], 200);
    }

    public function removeGroupChatMember(RemoveGroupChatMemberRequest $request)
    {
        $user = $request->user();
        $chatId = $request->route('chatId');
        $memberId = $request->route('memberId');

        $chat = Chat::where('id', $chatId)
            ->where('type', 'group')
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Only group admin can remove members
        $currentMember = $chat->members()->where('user_id', $user->id)->first();
        if ($currentMember->role !== 'admin') {
            return response([
                'message' => 'Unauthorized to remove members from this chat'
            ], 403);
        }

        if ($memberId == $currentMember->id) {
            return response([
                'message' => 'You cannot remove yourself from the chat. Use leave chat instead.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Remove member
            $memberToRemove = ChatMember::where('chat_id', $chat->id)
                ->where('id', $memberId)
                ->firstOrFail();

            $memberToRemove->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'message' => 'Failed to remove member from chat'
            ], 500);
        }
        return response([
            'message' => 'Member removed successfully'
        ], 200);
    }

    public function getChatMessages(GetChatMessagesRequest $request, $chatId)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);

        // Verify user is a member of this chat
        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        $messages = ChatMessage::where('chat_id', $chatId)
            ->with('creator')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response([
            'chat_messages' => ChatMessageResource::collection($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ], 200);
    }

    public function createChatMessage(CreateChatMessageRequest $request, $chatId)
    {
        $user = $request->user();

        // Verify user is a member of this chat
        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        $message = ChatMessage::create([
            'chat_id' => $chatId,
            'creator_id' => $user->id,
            'type' => 'text',
            'content' => $request->input('content'),
        ]);

        return response([
            'message' => 'Message created.',
            'chat_message' => new ChatMessageResource($message->load('creator'))
        ], 201);
    }

    public function updateChatMessage(UpdateChatMessageRequest $request, $chatId, $messageId)
    {
        $user = $request->user();

        // Verify user is a member of this chat
        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Find message and verify it belongs to the user
        $message = ChatMessage::where('id', $messageId)
            ->where('chat_id', $chatId)
            ->where('creator_id', $user->id)
            ->where('type', 'text') // Only allow editing text messages
            ->firstOrFail();

        $message->update([
            'content' => $request->input('content'),
        ]);

        return response([
            'message' => 'Message updated.',
            'chat_message' => new ChatMessageResource($message->load('creator'))
        ], 200);
    }

    public function deleteChatMessage(DeleteChatMessageRequest $request, $chatId, $messageId)
    {
        $user = $request->user();

        // Verify user is a member of this chat
        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Find message and verify it belongs to the user
        $message = ChatMessage::where('id', $messageId)
            ->where('chat_id', $chatId)
            ->where('creator_id', $user->id)
            ->firstOrFail();

        $message->delete();

        return response([
            'message' => 'Message deleted.'
        ], 200);
    }

    public function markAllChatMessagesAsSeen(MarkAllChatMessagesAsSeenRequest $request, $chatId)
    {
        $user = $request->user();

        // Verify user is a member of this chat
        $chat = Chat::where('id', $chatId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Mark all messages as seen except those created by current user
        ChatMessage::where('chat_id', $chatId)
            ->where('creator_id', '!=', $user->id)
            ->whereNull('seen_at')
            ->update(['seen_at' => now()]);

        return response([
            'message' => 'All messages marked as seen.'
        ], 200);
    }
}
