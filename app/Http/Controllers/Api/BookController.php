<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class BookController extends ApiController
{
    /**
     * Display a listing of books.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $books = QueryBuilder::for(Book::class)
            ->allowedIncludes(['category'])
            ->allowedFilters([
                'category_id',
                AllowedFilter::scope('available'),
                AllowedFilter::scope('search'),
            ])
            ->defaultSort('title')
            ->allowedSorts(['title', 'author', 'created_at'])
            ->paginate($request->get('per_page', 15));

        return BookResource::collection($books);
    }

    /**
     * Store a newly created book.
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = Book::create($request->validated());
        $book->load('category');

        return $this->successResponse(
            new BookResource($book),
            'Book created successfully',
            201
        );
    }

    /**
     * Display the specified book.
     */
    public function show(Book $book): JsonResponse
    {
        $book->load('category');

        return $this->successResponse(
            new BookResource($book),
            'Book retrieved successfully'
        );
    }

    /**
     * Update the specified book.
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $book->update($request->validated());
        $book->load('category');

        return $this->successResponse(
            new BookResource($book),
            'Book updated successfully'
        );
    }

    /**
     * Remove the specified book.
     */
    public function destroy(Book $book): JsonResponse
    {
        if ($book->hasActiveBorrows()) {
            return $this->errorResponse(
                'Cannot delete book with active borrows. Please wait until returned.',
                400
            );
        }

        $book->delete();

        return $this->successResponse(null, 'Book deleted successfully');
    }
}
