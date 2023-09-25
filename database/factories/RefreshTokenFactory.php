<?php

namespace Database\Factories;

use App\Models\RefreshToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RefreshToken>
 */
class RefreshTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expires_in' => now()->addMinutes(config('auth.refresh_token_ttl'))->unix(),
            'user_id' => UserFactory::new(),
        ];
    }

    public function expired(): static
    {
        $pastUnixDate = now()->subDays(1)->unix();

        return $this->state(fn(array $attributes) => [
            'expires_in' => $pastUnixDate,
        ]);
    }
}
