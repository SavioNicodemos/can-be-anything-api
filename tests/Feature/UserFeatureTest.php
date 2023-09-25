<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('can register', function () {
    $response = $this->postJson(route('register-user'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'tel' => '123456789',
        'password' => 'testPassword',
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});

test('can get user data', function () {
    $user = User::factory()->create();

    // Attempt to get user data.
    $response = $this->actingAs($user, 'sanctum')->getJson(route('users.me'));

    // Check the response.
    $response->assertStatus(200);
    expect($response->json()['data']['id'])->toEqual($user->id);
});
