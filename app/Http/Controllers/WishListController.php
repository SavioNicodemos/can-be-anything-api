<?php

namespace App\Http\Controllers;

use App\Exceptions\NotAuthorizedException;
use App\Http\Requests\StoreWishListRequest;
use App\Http\Requests\UpdateWishListRequest;
use App\Services\WishListService;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Throwable;

class WishListController extends Controller
{
    use ApiResponser;

    public function __construct(public WishListService $wishlistService)
    {
    }

    public function index(string $username): JsonResponse
    {
        $wishlists = $this->wishlistService->getWishListByUsername($username);

        return $this->successResponse($wishlists);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreWishListRequest $request): JsonResponse
    {
        $requestData = $request->validated();
        $requestData['slug'] ??= null;

        $wishlist = $this->wishlistService->create($requestData);

        return $this->successResponse($wishlist);
    }

    public function show(string $wishListId): JsonResponse
    {
        $wishList = $this->wishlistService->getWishListById($wishListId);

        return $this->successResponse($wishList);
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateWishListRequest $request, string $id): JsonResponse
    {
        $wishList = $this->wishlistService->update($request->validated(), $id);

        return $this->successResponse($wishList);
    }

    /**
     * @throws NotAuthorizedException
     */
    public function destroy(string $wishList): JsonResponse
    {
        try {
            $this->wishlistService->delete($wishList);

            return $this->successResponse(null, message: 'Wish list deleted successfully');
        } catch (NotAuthorizedException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
