<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\OAuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\BackupController;
use App\Http\Controllers\API\ChatController;
use Illuminate\Support\Facades\Route;

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/signin', [AuthController::class, 'signin']);
Route::get('/verify/email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verify.email');
Route::post('/send/verification-email', [AuthController::class, 'sendVerificationEmail']);
Route::post('/send/reset-password-email', [AuthController::class, 'sendResetPasswordEmail']);
Route::post('/set/new-password', [AuthController::class, 'setNewPassword'])->name('set.new-password');


Route::get('/{driver}/oauth/redirect', [OAuthController::class, 'oAuthRedirect']);
Route::get('/{driver}/oauth/callback', [OAuthController::class, 'oAuthCallback']);
Route::post('/oauth/exchange/token', [OAuthController::class, 'oAuthExchangeToken'])->middleware('auth:sanctum');



Route::middleware(['auth:sanctum', 'enabled'])->group(function () {
    Route::post('/signout', [AuthController::class, 'signout']);
    Route::get('/verify', [AuthController::class, 'verify']);
    Route::put('/create/password', [AuthController::class, 'createPassword']);
    Route::put('/change/password', [AuthController::class, 'changePassword']);
    Route::put('/update/profile-image', [AuthController::class, 'updateProfileImage']);
    Route::delete('/delete/profile-image', [AuthController::class, 'deleteProfileImage']);

    Route::middleware('admin')->prefix('manage')->group(function () {
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'getUsers']);
            Route::get('/read/{id}', [UserController::class, 'readUser']);
            Route::post('/create', [UserController::class, 'createUser']);
            Route::put('/update/{id}', [UserController::class, 'updateUser']);
            Route::patch('/toggle-status/{id}', [UserController::class, 'toggleUserStatus']);
            Route::delete('/delete/{id}', [UserController::class, 'deleteUser']);
        });
        Route::prefix('backups')->group(function () {
            Route::get('/', [BackupController::class, 'getBackups']);
            Route::post('/create', [BackupController::class, 'createBackup']);
            Route::get('/download/{filename}', [BackupController::class, 'downloadBackup']);
            Route::delete('/delete/{filename}', [BackupController::class, 'deleteBackup']);
        });
    });

    Route::prefix('chats')->group(function () {
        Route::get('/', [ChatController::class, 'getChats']);
        Route::get('/users', [ChatController::class, 'getChatUsers']);
        // Chat creation and management
        Route::post('/personal/create', [ChatController::class, 'createPersonalChat']);
        Route::post('/group/create', [ChatController::class, 'createGroupChat']);
        Route::get('/read/{chatId}', [ChatController::class, 'readChat']);
        Route::delete('/delete/{chatId}', [ChatController::class, 'deleteChat']);
        Route::put('/group/update/{chatId}', [ChatController::class, 'updateGroupChat']);
        Route::delete('/group/leave/{chatId}', [ChatController::class, 'leaveGroupChat']);

        Route::get('/group/{chatId}/members', [ChatController::class, 'getGroupChatMembers']);
        Route::post('/group/{chatId}/members/add', [ChatController::class, 'addGroupChatMember']);
        Route::delete('/group/{chatId}/members/remove/{memberId}', [ChatController::class, 'removeGroupChatMember']);

        Route::get('/{chatId}/messages', [ChatController::class, 'getChatMessages']);
        Route::post('/{chatId}/messages/create', [ChatController::class, 'createChatMessage']);
        Route::patch('/{chatId}/messages/update/{messageId}', [ChatController::class, 'updateChatMessage']);
        Route::delete('/{chatId}/messages/delete/{messageId}', [ChatController::class, 'deleteChatMessage']);
        Route::post('/{chatId}/messages/seen-all', [ChatController::class, 'markAllChatMessagesAsSeen']);
    });
});
