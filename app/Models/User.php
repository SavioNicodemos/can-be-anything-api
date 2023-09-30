<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\MailResetPasswordNotification;
use App\Notifications\VerifyEmail;
use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new MailResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail());
    }

    /**
     * Get the user's image.
     */
    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    /**
     * Get the wishlists for the user.
     */
    public function wishLists(): HasMany
    {
        return $this->hasMany(WishList::class);
    }

    public static function getLoggedUserId(): string
    {
        return User::getLoggedUser()->getAuthIdentifier();
    }

    public static function getLoggedUser(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        return auth()->user();
    }

    public function hasAccessTo($model, User $user = null): bool
    {
        if (! $user) {
            $user = $this->find(User::getLoggedUserId());
        }

        if ($user['is_admin']) {
            return true;
        }

        if ($model instanceof WishList) {
            return $model->user_id === $user->id;
        }

        if ($model instanceof Product) {
            return $model->wishlist->user_id === $user->id;
        }

        return false;
    }
}
