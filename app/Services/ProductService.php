<?php

namespace App\Services;

use App\Exceptions\NotAuthorizedException;
use App\Exceptions\NotFoundException;
use App\Helpers\ArrayHelper;
use App\Models\Product;
use App\Models\User;
use App\Models\WishList;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductService
{
    protected string $cacheKey;

    public function __construct()
    {
        $this->cacheKey = 'my_products';
    }

    /**
     * @throws Throwable
     */
    public function create(array $request): ?Product
    {
        if (is_array($request['image_links'])) {
            $request['image_links'] = ArrayHelper::uniqueValues($request['image_links']);
        }

        $user = User::find(User::getLoggedUserId());

        $wishList = WishList::findOrFail($request['wish_list_id']);

        if (!$user->hasAccessTo($wishList, $user)) {
            throw new NotAuthorizedException('WishList');
        }

        DB::beginTransaction();
        try {
            $product = new Product();
            $product->name = $request['name'];
            $product->description = $request['description'];

            $product->use_price_range = $request['use_price_range'];
            $product->price_min = $request['price_min'] ?? null;
            $product->price_max = $request['price_max'] ?? null;

            $product->use_quantity = $request['use_quantity'];
            $product->quantity = $request['quantity'] ?? null;

            $product->image_links = $request['image_links'] ?? [];

            $product->is_active = $request['is_active'] ?? true;

            $product->wish_list_id = $request['wish_list_id'];

            $product->save();

            DB::commit();

            Cache::forget($this->cacheKey . $user['id']);

            return $product;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function findOneById(string $productId): array
    {
        $product = Product::with([
            'user:id,name,username',
            'user.image:imageable_id,name',
        ])->findOrFail($productId);

        $product = $product->toArray();

        $product['user']['avatar'] = $product['user']['image']['name'];
        unset($product['user']['image']);
        unset($product['user']['id']);

        return $product;
    }

    public function getProducts(string $username, string $wishListSlug): LengthAwarePaginator
    {
        $user = User::where('username', $username)->firstOrFail();
        $userId = $user->id;

        $products = Product::whereHas('wishList', function (Builder $query) use ($username, $wishListSlug, $userId) {
            $query->where('slug', $wishListSlug)
                ->where('user_id', $userId);
        })
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return $products;
    }

    /**
     * @throws NotAuthorizedException
     */
    public function delete(string $productId): bool
    {
        $product = Product::findOrFail($productId);

        $userId = User::getLoggedUserId();
        $user = User::find($userId);

        if (!$user->hasAccessTo($product, $user)) {
            throw new NotAuthorizedException('Product');
        }

        $product->delete();

        Cache::forget($this->cacheKey . $userId);

        return true;
    }

    /**
     * @throws Throwable
     * @throws NotFoundException
     */
    public function update(array $filters, string $productId): Product
    {
        $product = Product::findOrFail($productId);
        $userId = User::getLoggedUserId();
        $user = User::find($userId);

        if (!$user->hasAccessTo($product, $user)) {
            throw new NotAuthorizedException('Product');
        }

        $newWishListId = $filters['wish_list_id'] ?? null;
        if ($newWishListId) {
            $wishList = WishList::findOrFail($filters['wish_list_id']);

            if (!$user->hasAccessTo($wishList, $user)) {
                throw new NotAuthorizedException('WishList');
            }
        }

        DB::beginTransaction();
        try {
            $product->name = $filters['name'] ?? $product->name;
            $product->description = $filters['description'] ?? $product->description;

            if ($newWishListId) {
                $product->wish_list_id = $wishList['id'];
            }

            $product->use_price_range = $filters['use_price_range'] ?? $product->use_price_range;
            $product->price_min = $filters['price_min'] ?? $product->price_min;
            $product->price_max = $filters['price_max'] ?? $product->price_max;

            $product->use_quantity = $filters['use_quantity'] ?? $product->use_quantity;
            $product->quantity = $filters['quantity'] ?? $product->quantity;

            $product->is_active = $filters['is_active'] ?? $product->is_active;

            $product->save();

            DB::commit();
            Cache::forget($this->cacheKey . $userId);

            return $product->fresh();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getMyProducts(array $filters): Collection
    {
        $userId = User::getLoggedUserId();

        return Product::where('user_id', $userId)
            ->where(function (Builder $query) use ($filters) {
                if (isset($filters['is_active'])) {
                    $query->where('is_active', $filters['is_active']);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * @throws Throwable
     */
    public function saveProductImages(array $image_links, string $productId): Product
    {
        $product = Product::findOrFail($productId);
        $images = ArrayHelper::uniqueValues($image_links['image_links']);

        DB::beginTransaction();
        try {
            $product->image_links = $images;

            $product->save();

            DB::commit();

            return $product;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function toggleIsActive(bool $isActive, string $productId): bool
    {
        $product = Product::findOrFail($productId);

        $user = User::find(User::getLoggedUserId());

        if (!$user->hasAccessTo($product, $user)) {
            throw new NotAuthorizedException('Product');
        }

        DB::beginTransaction();
        try {
            $product->is_active = $isActive;

            $product->save();

            DB::commit();

            return $isActive;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
