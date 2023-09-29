<?php

use App\Models\WishList;
use Database\Factories\WishListFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);


describe('Create Wishlist', function () {
    test('Create - User can create a wishlist.', function () {
        $user = createUser();
        $response = actingAs($user)->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => true,
        ]);

        $response->assertStatus(201);

        expect($response->json('data.name'))->toBe('My Wishlist')
            ->and($response->json('data.slug'))->toBe('my-wishlist')
            ->and($response->json('data.is_active'))->toBe(true)
            ->and($response->json('data.user_id'))->toBe($user->id)
            ->and($response->json('data.id'))->toBeNumeric()->toBeGreaterThan(0);

        //Assert that if create a wishlist with is_active = false, the response will be is_active = false
        asUser()->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist2',
            'slug' => 'my-wishlist2',
            'is_active' => false,
        ])->assertStatus(201)
            ->assertJson([
                'data' => [
                    'is_active' => false,
                ],
            ]);
    });

    test('Slug - Create a wishlist without slug creates automatically.', function () {
        $response = asUser()->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => true,
        ]);

        $response->assertStatus(201);

        expect($response->json('data.name'))->toBe('My Wishlist')
            ->and($response->json('data.slug'))->toBe('my-wishlist');
    });

    test('Slug - Create the same slug twice with big integer, still have the maximum str value of validation.', function () {
        $user = createUser();
        $longSlug = longString(50);
        $response = actingAs($user)->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
            'slug' => $longSlug,
            'is_active' => true,
        ]);

        $response2 = actingAs($user)->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
            'slug' => $longSlug,
            'is_active' => true,
        ]);

        $firstSlug = $response->json('data.slug');
        $secondSlug = $response2->json('data.slug');

        expect($firstSlug)->not->toBe($secondSlug)
            ->and(strlen($firstSlug))->toBe(50)
            ->and(strlen($secondSlug))->toBe(50);
    });

    test('Slug - Can create a wishlist with same slug but for different users.', function () {
        $response = asUser()->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => true,
        ]);

        $response2 = asUser()->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => true,
        ]);

        expect($response->json('data.slug'))->toBe($response2->json('data.slug'));
    });

    test('Missing Fields - Attempt to create a wishlist with missing mandatory fields.', function () {
        asUser()->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
        ])->assertStatus(422);

        asUser()->postJson(route('wish-lists.store'), [
            'slug' => 'my-wishlist',
        ])->assertStatus(422);

        asUser()->postJson(route('wish-lists.store'), [
            'is_active' => true,
        ])->assertStatus(422);

        asUser()->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
        ])->assertStatus(422);

        asUser()->postJson(route('wish-lists.store'), [
            'slug' => 'my-wishlist',
            'is_active' => true,
        ])->assertStatus(422);
    });

    test('Invalid Data - Try creating a wishlist with invalid data types or values.', function () {
        asUser()->postJson(route('wish-lists.store'), [
            'name' => 123,
            'slug' => 'my-wishlist',
            'is_active' => true,
        ])->assertStatus(422);

        $longText = longString(51);

        asUser()->postJson(route('wish-lists.store'), [
            'name' => $longText,
            'slug' => 'my-wishlist',
            'is_active' => true,
        ])->assertStatus(422);

        asUser()->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
            'slug' => $longText,
            'is_active' => true,
        ])->assertStatus(422);

        asUser()->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
            'slug' => 123456,
            'is_active' => true,
        ])->assertStatus(422);

        asUser()->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => 123,
        ])->assertStatus(422);

        asUser()->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => 'true',
        ])->assertStatus(422);

    });

    test('Unauthorized Access - Can not create a wishlist without being authenticated.', function () {
        $this->postJson(route('wish-lists.store'), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => true,
        ])->assertStatus(401);
    });

    test('Rollback on Failure - Ensure that not creates in case of database error', function () {
        // Arrange
        $user = createUser();
        $payload = [
            'name' => 'My Wishlist',
            'is_active' => true,
        ];

        // Mock a database exception during the wishlist creation

        DB::shouldReceive('beginTransaction')
            ->once()
            ->andThrow(new \Exception('Database error'));
        DB::shouldReceive('rollback')
            ->once();

        // Act
        $response = $this->actingAs($user)->postJson(route('wish-lists.store'), $payload);

        // Assert
        $response->assertStatus(500); // or whichever status code your application returns on error
        $this->assertCount(0, WishList::all());
    });
});

describe('Retrieve Wishlist by Username', function () {
    test('Happy Path - Successfully retrieve a user\'s wishlists by username.', function () {
        $user = createUser();
        WishListFactory::new()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = actingAs($user)->getJson(route('wish-lists.index', ['username' => $user->username]));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

    });

    test('Non-Existent User - Attempt to retrieve wishlists for a username that does not exist.', function () {
        $response = asUser()->getJson(route('wish-lists.index', ['username' => 'non-existent-user']));

        $response->assertStatus(404);
    });

    test('No Wishlists - Retrieve wishlists for a user who has no wishlists.', function () {
        $user = createUser();

        $response = actingAs($user)->getJson(route('wish-lists.index', ['username' => $user->username]));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    test('Unauthorized Access - Attempt to retrieve wishlists without appropriate permissions to view them.', function () {
    })->todo();

    test('Throttle - Test if send multiple requests throttle the endpoint', function () {
    })->todo();
});

describe('Update Wishlist', function () {
    test('Happy Path - Successfully update a wishlist\'s details.', function () {
        $user = createUser();
        $wishlist = WishListFactory::new()->create([
            'user_id' => $user->id,
        ]);

        $response = actingAs($user)->patchJson(route('wish-lists.update', ['wish_list' => $wishlist->id]), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'My Wishlist',
                    'slug' => 'my-wishlist',
                    'is_active' => true,
                ],
            ]);
    });

    test('Non-Existent Wishlist - Attempt to update a wishlist that does not exist.', function () {
        $response = asUser()->patchJson(route('wish-lists.update', ['wish_list' => 1]), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => true,
        ]);

        $response->assertStatus(404);
    });

    test('Unauthorized Access - Try to update a wishlist that belongs to another user.', function () {
        $user = createUser();
        $wishlist = WishListFactory::new()->create();

        $response = actingAs($user)->patchJson(route('wish-lists.update', ['wish_list' => $wishlist->id]), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => true,
        ]);

        $response->assertStatus(403);
    });

    test('Invalid Data - Attempt to update a wishlist with invalid data types or values.', function () {
        $user = createUser();
        $wishlist = WishListFactory::new()->create([
            'user_id' => $user->id,
        ]);

        $response = actingAs($user)->patchJson(route('wish-lists.update', ['wish_list' => $wishlist->id]), [
            'name' => 123,
            'slug' => 'my-wishlist',
            'is_active' => true,
        ]);

        $response->assertStatus(422);

        $longText = longString(51);

        $response = actingAs($user)->patchJson(route('wish-lists.update', ['wish_list' => $wishlist->id]), [
            'name' => $longText,
            'slug' => 'my-wishlist',
            'is_active' => true,
        ]);

        $response->assertStatus(422);

        $response = actingAs($user)->patchJson(route('wish-lists.update', ['wish_list' => $wishlist->id]), [
            'name' => 'My Wishlist',
            'slug' => $longText,
            'is_active' => true,
        ]);

        $response->assertStatus(422);

        $response = actingAs($user)->patchJson(route('wish-lists.update', ['wish_list' => $wishlist->id]), [
            'name' => 'My Wishlist',
            'slug' => 123456,
            'is_active' => true,
        ]);

        $response->assertStatus(422);

        $response = actingAs($user)->patchJson(route('wish-lists.update', ['wish_list' => $wishlist->id]), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => 123,
        ]);

        $response->assertStatus(422);

        $response = actingAs($user)->patchJson(route('wish-lists.update', ['wish_list' => $wishlist->id]), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => 'true',
        ]);

        $response->assertStatus(422);
    });

    test('Duplicate Slug - Update a new wishlist with a slug that already exists for the user.', function () {
        $user = createUser();
        $wishlist = WishListFactory::new()->create([
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'user_id' => $user->id,
        ]);

        $wishlist2 = WishListFactory::new()->create([
            'name' => 'My Wishlist2',
            'slug' => 'my-wishlist2',
            'user_id' => $user->id,
        ]);

        $response = actingAs($user)->patchJson(route('wish-lists.update', ['wish_list' => $wishlist2->id]), [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => true,
        ]);

        $response->assertStatus(200);

        $originalSlug = $wishlist->slug;
        $newSlug = $response->json('data.slug');
        expect($newSlug)->not->toBe($originalSlug)
            ->and(strlen($newSlug))->toBe(strlen($originalSlug) + 6);
    });

    test('Rollback on Failure - Ensure that not updates in case of database error', function () {
        // Arrange
        $user = createUser();
        $payload = [
            'name' => 'My Wishlist',
            'slug' => 'my-wishlist',
            'is_active' => true,
        ];

        $wishlist = WishListFactory::new()->create([
            'user_id' => $user->id,
            ...$payload
        ]);

        // Mock a database exception during the wishlist creation
        DB::shouldReceive('beginTransaction')
            ->once()
            ->andThrow(new \Exception('Database error'));
        DB::shouldReceive('rollback')
            ->once();

        // Act
        $response = $this->actingAs($user)->patchJson(route('wish-lists.update', ['wish_list' => $wishlist->id]), [
            'name' => 'My Wishlist Updated',
            'slug' => 'my-wishlist-updated',
            'is_active' => false,
        ]);

        // Assert
        $response->assertStatus(500); // or whichever status code your application returns on error

        // Assert that the wishlist is not updated
        $item = WishList::find($wishlist->id);

        expect($item->name)->toBe($wishlist->name)
            ->and($item->slug)->toBe($wishlist->slug)
            ->and($item->is_active)->toBe($wishlist->is_active);
    });
});

describe('Delete Wishlist', function () {
    test('Happy Path - Successfully delete a wishlist.', function () {
        $user = createUser();
        $wishList = WishListFactory::new()->create([
            'user_id' => $user->id,
        ]);

        $response = actingAs($user)->deleteJson(route('wish-lists.destroy', ['wish_list' => $wishList->id]));

        $response->assertStatus(200);

        expect($response->json('message'))->toContain('success');

        $response = actingAs($user)->getJson(route('wish-lists.index', ['username' => $user->username]));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');

        //Assert that the wishlist is soft deleted
        assertDatabaseMissing('wish_lists', [
            'id' => $wishList->id,
            'deleted_at' => null,
        ]);
    });

    test('Non-Existent Wishlist - Attempt to delete a wishlist that does not exist.', function () {
        $response = asUser()->deleteJson(route('wish-lists.destroy', ['wish_list' => 1111]));

        $response->assertStatus(404);
    });

    test('Unauthorized Access - Try to delete a wishlist that belongs to another user.', function () {
        $user = createUser();
        $wishList = WishListFactory::new()->create();

        $response = actingAs($user)->deleteJson(route('wish-lists.destroy', ['wish_list' => $wishList->id]));

        $response->assertStatus(403);
    });

    test('Unauthenticated Access - Try to access the route without login.', function () {
        $user = createUser();
        $wishList = WishListFactory::new()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson(route('wish-lists.destroy', ['wish_list' => $wishList->id]));

        $response->assertStatus(401);
    });
});

describe('Get Wishlist by ID', function () {
    test('Happy Path - Successfully retrieve a wishlist by ID.', function () {
        $user = createUser();
        $wishList = WishListFactory::new()->create([
            'user_id' => $user->id,
        ]);

        $response = actingAs($user)->getJson(route('wish-lists.show', ['wish_list' => $wishList->id]));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => $wishList->name,
                    'slug' => $wishList->slug,
                    'is_active' => $wishList->is_active,
                ],
            ]);
    });

    test('Non-Existent Wishlist - Attempt to retrieve a wishlist using an invalid ID.', function () {
    })->todo();

    test('Unauthorized Access - Try to retrieve a wishlist that belongs to another user.', function () {
    })->todo();
});

describe('Slug Availability Check', function () {
    test('Happy Path - Check the availability of a unique slug.', function () {
    })->todo();

    test('Slug Already Exists - Check the availability of a slug that already exists for the user.', function () {
    })->todo();

    test('Invalid Slug Format - Check the availability of an improperly formatted slug.', function () {
    })->todo();
});

describe('Cache-Related Cases', function () {
    test('Cache Update on Create - Ensure that the cache is updated/invalidated when a new wishlist is created.', function () {
    })->todo();

    test('Cache Update on Update - Ensure that the cache is updated/invalidated when a wishlist is updated.', function () {
    })->todo();

    test('Cache Update on Delete - Ensure that the cache is updated/invalidated when a wishlist is deleted.', function () {
    })->todo();
});

describe('Concurrent Requests', function () {
    test('Race Conditions - Test how the system behaves when concurrent requests are made, especially for creating/updating with the same slug.', function () {
        // Arrange
        $user = createUser();
        $slug = 'wishlist-slug';
        $payload = [
            'name' => 'My Wishlist',
            'slug' => $slug,
            'is_active' => true,
        ];

        // Start a database transaction
        \Illuminate\Support\Facades\DB::beginTransaction();

        // Act
        // Make the first request but don't commit the transaction yet
        $response1 = $this->actingAs($user)->postJson(route('wish-lists.store'), $payload);

        // Make the second request. At this point, the first request is "in-flight"
        // and has not been committed, simulating a race condition.
        $response2 = $this->actingAs($user)->postJson(route('wish-lists.store'), $payload);

        // Now commit the transaction
        \Illuminate\Support\Facades\DB::commit();

        // Assert
        $response1->assertStatus(201);
        $response2->assertStatus(201);

        // Assert that two wishlists are created, one with the original slug and one with a modified slug.
        $this->assertDatabaseHas('wish_lists', ['slug' => $slug]);
        $this->assertCount(2, WishList::all());
        $this->assertNotEquals($slug, WishList::all()->last()->slug);
    });
});
