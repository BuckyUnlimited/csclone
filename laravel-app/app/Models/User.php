<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\EmailVerificationNotification;
use App\Notifications\ResetPasswordNotification;
use App\Services\ImageClassService;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'profile_image'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, MustVerifyEmail;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sendEmailVerificationNotification($callback_url = null)
    {
        $this->notify(new EmailVerificationNotification($callback_url));
    }

    public function sendPasswordResetNotification($token, $callback_url = null)
    {
        $this->notify(new ResetPasswordNotification($token, $callback_url));
    }

    protected function passwordNull(): Attribute
    {
        return Attribute::make(
            get: fn() => empty($this->password),
        );
    }

    // profile image related methods and attributes
    protected function profileImage(): Attribute
    {
        return Attribute::make(
            get: function () {
                $imageClass = ImageClassService::forUserModel();
                $imagePath = $this->getRawOriginal('profile_image');
                return $imageClass->fullUrl($imagePath);
            },
        );
    }

    protected function profileThumbnail(): Attribute
    {
        return Attribute::make(
            get: function () {
                $imageClass = ImageClassService::forUserModel();
                $thumbnailPath = $imageClass->thumbnailPath($this->getRawOriginal('profile_image'));
                return $imageClass->fullUrl($thumbnailPath);
            },
        );
    }
    // end profile image related methods and attributes

}
