<?php

namespace App\Services;

use App\Exceptions\NotAuthorizedException;
use App\Exceptions\NotFoundException;
use App\Helpers\ArrayHelper;
use App\Models\Product;
use Exception;
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
    public function create(array $request): Product|null
    {
        if (is_array($request['image_links'])) {
            $request['image_links'] = ArrayHelper::uniqueValues($request['image_links']);
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

            if (!!auth()->user()->getAuthIdentifier()) {
                $product->user_id = auth()->user()->getAuthIdentifier();
            }

            $product->save();

            DB::commit();

            Cache::forget($this->cacheKey . auth()->user()->getAuthIdentifier());

            return $product;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function findOneById(string $productId): array
    {
        $product = Product::with([
            'user:id,name,tel',
            'user.image:imageable_id,name'
        ])->findOrFail($productId);

        $product = $product->toArray();

        $product['user']['avatar'] = $product['user']['image']['name'];
        unset($product['user']['image']);
        unset($product['user']['id']);

        return $product;
    }

    /**
     * @throws NotFoundException
     * @throws NotAuthorizedException
     */
    public function delete(string $productId): bool
    {
        $product = Product::find($productId);

        if (!$product) {
            throw new NotFoundException('Product');
        }
        if ($product->user_id !== auth()->user()->getAuthIdentifier()) {
            throw new NotAuthorizedException('Product');
        }

        $product->delete();

        Cache::forget($this->cacheKey . auth()->user()->getAuthIdentifier());

        return true;
    }

    /**
     * @throws Throwable
     * @throws NotFoundException
     */
    public function update(array $filters, string $productId): Product
    {
        $product = Product::findOrFail($productId);

        if ($product->user_id !== auth()->user()->getAuthIdentifier()) {
            throw new NotAuthorizedException('Product');
        }

        DB::beginTransaction();
        try {
            $product->name = $filters['name'] ?? $product->name;
            $product->description = $filters['description'] ?? $product->description;

            $product->use_price_range = $filters['use_price_range'] ?? $product->use_price_range;
            $product->price_min = $filters['price_min'] ?? $product->price_min;
            $product->price_max = $filters['price_max'] ?? $product->price_max;

            $product->use_quantity = $filters['use_quantity'] ?? $product->use_quantity;
            $product->quantity = $filters['quantity'] ?? $product->quantity;

            $product->is_active = $filters['is_active'] ?? $product->is_active;

            $product->save();

            DB::commit();
            Cache::forget($this->cacheKey . auth()->user()->getAuthIdentifier());

            return $product->fresh();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getMyProducts(array $filters): Collection
    {
        $cachedValues = Cache::get($this->cacheKey . auth()->user()->getAuthIdentifier());
        if ($cachedValues) return $cachedValues;

        $myProducts = Product::where('user_id', auth()->user()->getAuthIdentifier())
            ->where(function (Builder $query) use ($filters) {
                if (isset($filters['is_active'])) {
                    $query->where('is_active', $filters['is_active']);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

        Cache::put($this->cacheKey . auth()->user()->getAuthIdentifier(), $myProducts);

        return $myProducts;
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
}
