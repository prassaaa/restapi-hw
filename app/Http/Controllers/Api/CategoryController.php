<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\BookCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends ApiController
{
    /**
     * Display a listing of book categories.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = BookCategory::withCount('books')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = BookCategory::create($request->validated());

        return $this->successResponse(
            new CategoryResource($category),
            'Category created successfully',
            201
        );
    }

    /**
     * Display the specified category.
     */
    public function show(BookCategory $category): JsonResponse
    {
        $category->loadCount('books');

        return $this->successResponse(
            new CategoryResource($category),
            'Category retrieved successfully'
        );
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, BookCategory $category): JsonResponse
    {
        $category->update($request->validated());

        return $this->successResponse(
            new CategoryResource($category),
            'Category updated successfully'
        );
    }

    /**
     * Remove the specified category.
     */
    public function destroy(BookCategory $category): JsonResponse
    {
        // Check if category has books
        if ($category->books()->count() > 0) {
            return $this->errorResponse(
                'Cannot delete category with associated books',
                400
            );
        }

        $category->delete();

        return $this->successResponse(
            null,
            'Category deleted successfully'
        );
    }
}

