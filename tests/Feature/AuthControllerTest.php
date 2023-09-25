<?php

use App\Models\User;
use Database\Factories\RefreshTokenFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can login', function () {
    // Create a user to test with.
    $user = User::factory()->create([
        'password' => bcrypt($password = 'testPassword'),
    ]);

    // Attempt to make login.
    $response = $this->postJson('api/v1/sessions', [
        'email' => $user->email,
        'password' => $password,
    ]);

    // Check the response.
    $response->assertStatus(200);
    expect($response->json()['data'])->toHaveKey('token');
});

test('cannot login if not registered', function () {
    // Attempt to make login.
    $response = $this->postJson('api/v1/sessions', [
        'email' => 'nonExistent@email.com',
        'password' => 'nonExixtentP@55',
    ]);

    // Check the response.
    $response->assertStatus(404);
    expect($response->json())->toHaveKey('message');
});

test('cannot login with wrong password', function () {
    // Create a user to test with.
    $user = User::factory()->create([
        'password' => bcrypt('correctPassword'),
    ]);

    // Attempt to make login.
    $response = $this->postJson('api/v1/sessions', [
        'email' => $user->email,
        'password' => bcrypt('wrongPassword'),
    ]);

    // Check the response.
    $response->assertStatus(403);
    expect($response->json())->toHaveKey('message');
});

test('can logout', function () {
    $user = User::factory()->create();

    // Create a token for the user.
    $token = $user->createToken('test-token');

    // Now, for the test request, we need to pass the token as a Bearer token in the Authorization header
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->deleteJson('api/v1/sessions', []);

    // Check the response.
    $response->assertStatus(200);

    $tokenId = $token->accessToken->id;

    // Retrieve the token from the database using the token's ID
    $retrievedToken = \Laravel\Sanctum\PersonalAccessToken::findToken($tokenId);

    // The token should be null since it has been deleted
    expect($retrievedToken)->toBeNull();
    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
});

test('can refresh token', function () {
    // Create a user to test with.
    $user = User::factory()->create([
        'password' => bcrypt($password = 'testPassword'),
    ]);

    // Attempt to make login.
    $response = $this->postJson('api/v1/sessions', [
        'email' => $user->email,
        'password' => $password,
    ]);

    // Check the response.
    $responseRefreshToken = $this->postJson('api/v1/sessions/refresh-token', [
        'refresh_token' => $response->json()['data']['refresh_token'],
    ]);
    $responseRefreshToken->assertStatus(200);
    expect($response->json()['data']['token'] === $responseRefreshToken->json()['data']['token'])->toBeFalse()
        ->and($user->name)->toEqual($responseRefreshToken->json()['data']['user']['name']);
});

test('can not refresh an invalid token', function () {
    $responseRefreshToken = $this->postJson(route('refresh-token'), [
        'refresh_token' => fake()->uuid,
    ]);

    $responseRefreshToken->assertStatus(400);
    expect($responseRefreshToken->json()['message'])->toContain('Invalid');
});

test('can not refresh an expired token', function () {
    $refreshToken = RefreshTokenFactory::new()->expired()->create();

    $responseRefreshToken = $this->postJson(route('refresh-token'), [
        'refresh_token' => $refreshToken->id,
    ]);

    $responseRefreshToken->assertStatus(400);
    expect($responseRefreshToken->json()['message'])->toContain('Expired');
});
