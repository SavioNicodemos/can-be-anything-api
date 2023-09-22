<?php

namespace App\Services;

use App\Exceptions\NotAuthorizedException;
use App\Exceptions\NotFoundException;
use App\Models\Image;
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
    public function create($request): Product|null
    {
        DB::beginTransaction();
        try {
            $product = new Product();
            $product->name = $request['name'];
            $product->description = $request['description'];
            $product->is_new = $request['is_new'];
            $product->price = $request['price'];
            $product->accept_trade = $request['accept_trade'];
            if (!!auth()->user()->getAuthIdentifier()) {
                $product->user_id = auth()->user()->getAuthIdentifier();
            }
            $product->is_active = true;

            $product->save();

            DB::commit();

            Cache::forget($this->cacheKey . auth()->user()->getAuthIdentifier());

            return $product;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @throws NotFoundException
     */
    public function findOneById(string $productId): array
    {
        $product = Product::with([
            'user:id,name,tel',
            'user.image:imageable_id,name',
            'productImages' => function ($query) {
                return $query->select(['id', 'name as path', 'imageable_id']);
            },
        ])->find($productId);

        if (!$product) {
            throw new NotFoundException('Product');
        }

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
    public function update(array $filters, string $productId): bool
    {
        $product = Product::find($productId);
        if (!$product) {
            throw new NotFoundException('Product');
        }
        if ($product->user_id !== auth()->user()->getAuthIdentifier()) {
            throw new NotAuthorizedException('Product');
        }
        DB::beginTransaction();
        try {
            $product->name = $filters['name'] ?? $product->name;
            $product->description = $filters['description'] ?? $product->description;
            $product->price = $filters['price'] ?? $product->price;
            $product->is_new = $filters['is_new'] ?? $product->is_new;
            $product->accept_trade = $filters['accept_trade'] ?? $product->accept_trade;
            $product->is_active = $filters['is_active'] ?? $product->is_active;

            $product->save();

            DB::commit();
            Cache::forget($this->cacheKey . auth()->user()->getAuthIdentifier());

            return true;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function listNotMyProducts(array $filters): Collection
    {
        return Product::where('user_id', '!=', auth()->user()->getAuthIdentifier())
            ->where('is_active', true)
            ->where(function (Builder $query) use ($filters) {
                if (isset($filters['is_new'])) {
                    $query->where('is_new', $filters['is_new']);
                }
                if (isset($filters['accept_trade'])) {
                    $query->where('accept_trade', $filters['accept_trade']);
                }
                if (isset($filters['query'])) {
                    $query->where('name', 'LIKE', '%' . $filters['query'] . '%');
                }
            })
            ->with([
                'user:id,name,tel',
                'user.image:imageable_id,name',
                'productImages' => function ($query) {
                    return $query->select(['id', 'name as path', 'imageable_id']);
                },
            ])
            ->get(['id', 'name', 'price', 'is_new', 'accept_trade', 'user_id']);
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
            ->with([
                'productImages' => function ($query) {
                    return $query->select(['id', 'name as path', 'imageable_id']);
                },
            ])
            ->get();

        Cache::put($this->cacheKey . auth()->user()->getAuthIdentifier(), $myProducts);

        return $myProducts;
    }

    /**
     * @throws Throwable
     */
    public function saveProductImages(array $filters): Collection
    {
        $productId = $filters['product_id'];
        $images = $filters['images'];

        DB::beginTransaction();
        try {
            $product = Product::find($productId);
            if (!$product) {
                throw new NotFoundException('Product');
            }
            if ($product->user_id !== auth()->user()->getAuthIdentifier()) {
                throw new NotAuthorizedException('Product');
            }

            $imageService = new ImageService();

            $imagesObjects = [];
            foreach ($images as $image) {
                $imageObject = $imageService->storeImage($image, 'products');
                $imagesObjects[] = new Image($imageObject);
            }

            $product->productImages()->saveMany($imagesObjects);

            DB::commit();

            return Image::where('imageable_id', $productId)->get();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
