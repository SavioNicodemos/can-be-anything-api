<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register()
    {
        $response = $this->postJson('api/v1/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'tel' => '123456789',
            'password' => 'testPassword',
            // Assuming a test image file is stored in the `tests` directory.
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_can_login()
    {
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
        $this->assertArrayHasKey('token', $response->json());
    }

    public function test_cannot_login_if_not_registered()
    {
        // Attempt to make login.
        $response = $this->postJson('api/v1/sessions', [
            'email' => 'nonExistent@email.com',
            'password' => 'nonExixtentP@55',
        ]);

        // Check the response.
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    public function test_cannot_login_with_wrong_password()
    {
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
        $this->assertArrayHasKey('message', $response->json());
    }

    public function test_can_logout()
    {
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
        $this->assertNull($retrievedToken);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_can_refresh_token()
    {
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
            'refresh_token' => $response->json()['refresh_token'],
        ]);
        $responseRefreshToken->assertStatus(200);
        $this->assertFalse($response->json()['token'] === $responseRefreshToken->json()['token']);
        $this->assertEquals($responseRefreshToken->json()['user']['name'], $user->name);
    }

    public function test_can_get_user_data()
    {
        $user = User::factory()->create();

        // Attempt to get user data.
        $response = $this->actingAs($user, 'sanctum')->getJson('api/v1/users/me');

        // Check the response.
        $response->assertStatus(200);
        $this->assertEquals($user->id, $response->json()['id']);
    }
}
