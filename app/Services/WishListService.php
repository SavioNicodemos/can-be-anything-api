<?php

namespace App\Services;

use App\Exceptions\NotAuthorizedException;
use App\Models\User;
use App\Models\WishList;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class WishListService
{
    protected string $cacheKey;

    public function __construct()
    {
        $this->cacheKey = 'wish_lists_';
    }

    /**
     * @throws Throwable
     */
    public function create(array $request): WishList
    {
        $userId = User::getLoggedUserId();

        $slug = $request['slug'] ? Str::slug($request['slug']) : Str::slug($request['name']);

        if (!$this->checkSlugAvailability($slug, $userId)) {
            $baseSlug = strlen($slug) > 44 ? substr($slug, 0, 44) : $slug;
            $slug = $baseSlug . '-' . Str::random(5);
        }

        try {
            DB::beginTransaction();

            $wishList = new WishList();

            $wishList->name = $request['name'];
            $wishList->slug = $slug;
            $wishList->is_active = $request['is_active'] ?? true;

            if ($userId) {
                $wishList->user_id = $userId;
            }

            $wishList->save();

            DB::commit();

            Cache::forget($this->cacheKey . $userId);

            return $wishList;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function checkSlugAvailability(string $slug, string $userId, int $exceptId = null): bool
    {
        return !WishList::where('slug', $slug)
            ->where('user_id', $userId)
            ->when($exceptId, function (Builder $query) use ($exceptId) {
                $query->where('id', '!=', $exceptId);
            })
            ->exists();
    }

    public function getWishListByUsername(string $username): LengthAwarePaginator
    {
        User::where('username', $username)->firstOrFail();

        return WishList::withCount('products')
            ->whereHas('user', function ($query) use ($username) {
                $query->where('username', $username);
            })
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->paginate(12);
    }

    /**
     * @throws NotAuthorizedException
     */
    public function delete(string $wishListId): bool
    {
        $wishList = WishList::findOrFail($wishListId);
        $userId = User::getLoggedUserId();

        if ($wishList->user_id !== $userId) {
            throw new NotAuthorizedException('Wish List');
        }

        $wishList->delete();

        Cache::forget($this->cacheKey . $userId);

        return true;
    }

    /**
     * @throws Throwable
     */
    public function update(array $filters, string $id): WishList
    {
        $wishList = WishList::findOrFail($id);
        $userId = User::getLoggedUserId();

        if ($wishList->user_id !== $userId) {
            throw new NotAuthorizedException('Wish List');
        }

        $slug = $filters['slug'] ? Str::slug($filters['slug']) : $wishList->slug;

        if (!$this->checkSlugAvailability($slug, $userId, exceptId: $wishList->id)) {
            $baseSlug = strlen($slug) > 44 ? substr($slug, 0, 44) : $slug;
            $slug = $baseSlug . '-' . Str::random(5);
        }

        try {
            DB::beginTransaction();

            $wishList->name = $filters['name'] ?? $wishList->name;
            $wishList->slug = $slug;
            $wishList->is_active = $filters['is_active'] ?? $wishList->is_active;

            $wishList->save();

            DB::commit();
            Cache::forget($this->cacheKey . $userId);

            return $wishList->fresh();
        } catch (NotAuthorizedException|Exception|Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }


    public function getWishListById(string $wishListId): WishList
    {
        return WishList::findOrFail($wishListId);
    }
}
