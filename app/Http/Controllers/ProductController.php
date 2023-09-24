<?php

namespace App\Http\Controllers;

use App\Exceptions\NotAuthorizedException;
use App\Exceptions\NotFoundException;
use App\Http\Requests\AddImageProductRequest;
use App\Http\Requests\ListMyProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ProductController extends Controller
{
    use ApiResponser;

    public function __construct(public ProductService $productService)
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        return $this->successResponse($this->productService->create($request->validated()), 201);
    }

    public function show(string $id): JsonResponse
    {
        return $this->successResponse($this->productService->findOneById($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws NotFoundException
     * @throws Throwable
     */
    public function update(UpdateProductRequest $request, string $productId): JsonResponse
    {
        return $this->successResponse(
            $this->productService->update(
                $request->validated(),
                $productId
            )
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws NotFoundException
     * @throws NotAuthorizedException
     */
    public function destroy(string $productId): JsonResponse
    {
        return $this->successResponse(
            data: $this->productService->delete($productId),
            message: 'Product deleted successfully',
        );
    }

    public function getMyProducts(ListMyProductsRequest $request): JsonResponse
    {
        return $this->successResponse($this->productService->getMyProducts($request->validated()));
    }

    /**
     * @throws Throwable
     */
    public function changeImages(AddImageProductRequest $request, string $productId): JsonResponse
    {
        return $this->successResponse(
            $this->productService->saveProductImages(
                $request->validated(),
                $productId
            ),
        );
    }

    /**
     * @throws Throwable
     */
    public function toggleActive(Request $request, string $productId): JsonResponse
    {
        $validated = $this->validate($request, [
            'is_active' => ['required', 'boolean'],
        ]);
        return $this->successResponse(
            $this->productService->toggleIsActive(
                $validated['is_active'],
                $productId
            ),
        );
    }
}
