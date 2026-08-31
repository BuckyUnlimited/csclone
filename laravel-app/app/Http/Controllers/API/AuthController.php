<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\CreatePasswordRequest;
use App\Http\Requests\User\SendResetPasswordEmailRequest;
use App\Http\Requests\User\SendVerificationEmailRequest;
use App\Http\Requests\User\SetNewPasswordRequest;
use App\Http\Requests\User\SigninRequest;
use App\Http\Requests\User\SignupRequest;
use App\Http\Requests\User\UpdateProfileImageRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\ImageClassService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    function signup(SignupRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        $user->sendEmailVerificationNotification($request->callback_url);

        return response([
            'message' => 'User signed up.',
            'user' => new UserResource($user)
        ], 201);
    }

    function signin(SigninRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email is not verified.',
            ]);
        }

        if ($user->status === 'DISABLED') {
            throw ValidationException::withMessages([
                'email' => 'Your account has been disabled. Please contact support.',
            ]);
        }

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password does not match.',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response([
            'message' => 'User signed in.',
            'user' => new UserResource($user),
            'token' => $token
        ], 200);
    }

    function signout(Request $request)
    {
        $user = $request->user();

        // option 1
        $user->currentAccessToken()->delete();

        // option 2
        $currentToken = $user->currentAccessToken();
        $user->tokens()->where('id', $currentToken->id)->delete();

        return response([
            'message' => 'User signed out.'
        ], 200);
    }

    function verify(Request $request)
    {
        return response([
            'message' => 'Token is valid.',
            'user' => new UserResource($request->user())
        ], 200);
    }

    function verifyEmail(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email is already verified.',
            ]);
        }

        $user->markEmailAsVerified();

        return response([
            'message' => 'Email verified successfully.'
        ], 200);
    }

    function sendVerificationEmail(SendVerificationEmailRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email is already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification($request->callback_url);

        return response([
            'message' => 'Verification email resent.'
        ], 200);
    }

    function sendResetPasswordEmail(SendResetPasswordEmailRequest $request)
    {
        $status = Password::sendResetLink(
            ['email' => $request->email],
            function ($user, $token) use ($request) {
                $user->sendPasswordResetNotification($token, $request->callback_url);
            }
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response([
                'message' => 'Password reset link sent to your email'
            ], 200);
        }

        return response([
            'message' => 'Password reset link sent to your email'
        ], 200);
    }

    function setNewPassword(SetNewPasswordRequest $request)
    {
        $status = Password::reset(
            [
                'token' => $request->token,
                'email' => $request->email,
                'password' => $request->password,
                'password_confirmation' => $request->password_confirmation
            ],
            function ($user, $password) {
                $user->password = $password;
                $user->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'password' => [__($status)],
            ]);
        }

        return response([
            'message' => 'Password has been reset successfully.'
        ], 200);
    }

    function createPassword(CreatePasswordRequest $request)
    {
        $user = $request->user();
        if (!empty($user->password)) {
            throw ValidationException::withMessages([
                'new_password' => 'Password is already set.',
            ]);
        }
        $user->password = $request->new_password;
        $user->save();
        $user->tokens()->delete();
        return response([
            'message' => 'Password created successfully.'
        ], 200);
    }

    function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Current password does not match.',
            ]);
        }
        if ($request->current_password === $request->new_password) {
            throw ValidationException::withMessages([
                'new_password' => 'New password must be different from current password.',
            ]);
        }
        $user->password = Hash::make($request->new_password);
        $user->save();
        $user->tokens()->delete();
        return response([
            'message' => 'Password changed successfully.'
        ], 200);
    }

    function updateProfileImage(UpdateProfileImageRequest $request)
    {
        $imageClass = ImageClassService::forUserModel();
        $user = $request->user();
        $oldImage = $user->getRawOriginal('profile_image');
        $newImage = null;

        try {
            $newImage = $imageClass->store($request->file('profile_image'));
            $user->profile_image = $newImage;
            $user->save();
        } catch (Exception $e) {
            // Save failed - delete the newly stored file so no orphan is left.
            $imageClass->delete($newImage);
            throw $e;
        }

        // Save succeeded — safe to delete the old file now.
        $imageClass->delete($oldImage);

        return response([
            'message' => 'User profile image updated successfully.',
            'profile_image' => $user->profile_image,
            'profile_thumbnail' => $user->profile_thumbnail,
        ], 200);
    }

    function deleteProfileImage(Request $request)
    {
        $imageClass = ImageClassService::forUserModel();
        $user = $request->user();
        $oldImage = $user->getRawOriginal('profile_image');

        $user->profile_image = null;
        $user->save();

        // Save succeeded — safe to delete the file now.
        $imageClass->delete($oldImage);

        return response([
            'message' => 'User profile image deleted successfully.',
        ], 200);
    }
}
