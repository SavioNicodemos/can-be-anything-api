<?php

namespace App\Services;

use App\Models\Image;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class UserService
{
    /**
     * @throws Throwable
     */
    public function create($request): bool
    {
        DB::beginTransaction();
        try {
            $request['password'] = Hash::make($request['password']);
            $request['remember_token'] = Str::random(10);

            $user = User::create($request->toArray());

            $imageService = new ImageService();

            $imageObject = $imageService->storeImage($request->file('avatar'), 'avatars');

            $image = new Image($imageObject);

            $user->image()->save($image);

            DB::commit();

            event(new Registered($user));

            return true;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getUserData($userId): array
    {
        $user = User::with('image')->find($userId);

        return [
            'id' => $user->id,
            'avatar' => $user->image->name ?? null,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
        ];
    }

    /**
     * @throws Exception
     */
    public function resendEmailVerification(string $userId): bool
    {
        $user = User::findOrFail($userId);

        $user->sendEmailVerificationNotification();

        return true;
    }

    public function getByUsername(string $username): array
    {
        $user = User::with('image')->where('username', $username)->firstOrFail();

        return [
            'avatar' => $user->image->name ? url('api/v1/images/'. $user->image->name) : null,
            'name' => $user->name,
            'username' => $user->username,
        ];
    }
}
