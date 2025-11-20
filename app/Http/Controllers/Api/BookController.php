<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends ApiController
{
    /**
     * Display a listing of books.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Book::with('category');

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by availability
        if ($request->has('available') && $request->available) {
            $query->where('available_copies', '>', 0);
        }

        // Search by title, author, or ISBN
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'title');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $books = $query->paginate($perPage);

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
        // Check if book has active borrows
        if ($book->borrows()->active()->count() > 0) {
            return $this->errorResponse(
                'Cannot delete book with active borrows. Please wait until all copies are returned.',
                400
            );
        }

        $book->delete();

        return $this->successResponse(
            null,
            'Book deleted successfully'
        );
    }
}

