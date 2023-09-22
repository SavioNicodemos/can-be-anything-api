<?php

namespace App\Services;

use App\Exceptions\ApplicationException;
use App\Exceptions\NotFoundException;
use App\Models\RefreshToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use JetBrains\PhpStorm\ArrayShape;

class AuthService
{
    /**
     * @throws NotFoundException|ApplicationException
     */
    #[ArrayShape(['token' => "\Laravel\Sanctum\string|string", 'user' => 'mixed', 'refresh_token' => 'string'])]
    public function loginWithPasswordAndEmail(array $validated): array
    {
        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            throw new NotFoundException('User');
        }
        if (! Hash::check($validated['password'], $user->password)) {
            throw new ApplicationException('Password Mismatch', 403);
        }

        return $this->loginByUserId($user->id);
    }

    #[ArrayShape(['token' => "\Laravel\Sanctum\string|string", 'user' => 'mixed', 'refresh_token' => 'string'])]
    private function loginByUserId(string $userId): array
    {
        $user = User::with('image')->find($userId);
        $token = $user->createToken('web')->plainTextToken;
        $refreshToken = $this->createRefreshToken($user->id);
        $user['avatar'] = $user->image->name ?? null;
        unset($user->image);

        return [
            'token' => $token,
            'user' => $user,
            'refresh_token' => $refreshToken,
        ];
    }

    public function createRefreshToken(string $userId): string
    {
        $carbon = new Carbon();
        $now = $carbon->now();
        $refreshToken = RefreshToken::create([
            'expires_in' => $now->addMinutes(config('auth.refresh_token_ttl'))->unix(),
            'user_id' => $userId,
        ]);

        return $refreshToken->id;
    }

    /**
     * @throws ApplicationException
     */
    public function refreshToken(array $validatedRequest): array
    {
        $refreshTokenInstance = DB::table('refresh_tokens')->where('id', $validatedRequest['refresh_token']);

        $refreshTokenObject = $refreshTokenInstance->first();

        if (! $refreshTokenObject) {
            throw new ApplicationException('Invalid refresh token');
        }
        if (Carbon::parse($refreshTokenObject->expires_in)->isBefore(Carbon::now())) {
            $refreshTokenInstance->delete();
            throw new ApplicationException('Expired refresh token');
        }

        $refreshTokenInstance->delete();

        return $this->loginByUserId($refreshTokenObject->user_id);
    }
}
