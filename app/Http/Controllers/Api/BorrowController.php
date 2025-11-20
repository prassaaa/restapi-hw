<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BorrowBookRequest;
use App\Http\Resources\BorrowResource;

use App\Models\Book;
use App\Models\BookBorrow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BorrowController extends ApiController
{
    /**
     * List borrowed books with filtering.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = BookBorrow::with(['book', 'user']);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'returned') {
                $query->returned();
            } elseif ($request->status === 'overdue') {
                $query->overdue();
            }
        } else {
            // Default: show only active borrows
            $query->active();
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by book
        if ($request->has('book_id')) {
            $query->where('book_id', $request->book_id);
        }

        // Filter by current user's borrows
        if ($request->has('my_borrows') && $request->my_borrows) {
            $query->where('user_id', $request->user()->id);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'borrowed_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $borrows = $query->paginate($perPage);

        return BorrowResource::collection($borrows);
    }

    /**
     * Borrow a book.
     */
    public function borrow(BorrowBookRequest $request, Book $book): JsonResponse
    {
        // Check if book is available
        if (!$book->isAvailable()) {
            return $this->errorResponse(
                'This book is currently not available for borrowing',
                400
            );
        }

        // Check if user already has an active borrow for this book
        $existingBorrow = BookBorrow::where('user_id', $request->user()->id)
            ->where('book_id', $book->id)
            ->active()
            ->first();

        if ($existingBorrow) {
            return $this->errorResponse(
                'You have already borrowed this book and have not returned it yet',
                400
            );
        }

        // Check if user has reached maximum borrow limit (e.g., 5 books)
        $activeBorrowsCount = BookBorrow::where('user_id', $request->user()->id)
            ->active()
            ->count();

        if ($activeBorrowsCount >= 5) {
            return $this->errorResponse(
                'You have reached the maximum limit of borrowed books (5). Please return some books first.',
                400
            );
        }

        try {
            DB::beginTransaction();

            // Create borrow record
            $dueDays = $request->input('due_days', 14); // Default 14 days
            $borrow = BookBorrow::create([
                'user_id' => $request->user()->id,
                'book_id' => $book->id,
                'borrowed_at' => now(),
                'due_date' => now()->addDays($dueDays),
                'notes' => $request->input('notes'),
            ]);

            // Decrease available copies
            $book->decrementAvailableCopies();

            DB::commit();

            $borrow->load(['book', 'user']);

            return $this->successResponse(
                [
                    'id' => $borrow->id,
                    'book' => [
                        'id' => $borrow->book->id,
                        'title' => $borrow->book->title,
                        'author' => $borrow->book->author,
                    ],
                    'user' => [
                        'id' => $borrow->user->id,
                        'name' => $borrow->user->name,
                    ],
                    'borrowed_at' => $borrow->borrowed_at->toISOString(),
                    'due_date' => $borrow->due_date->toISOString(),
                    'notes' => $borrow->notes,
                ],
                'Book borrowed successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                'Failed to borrow book. Please try again.',
                500
            );
        }
    }

    /**
     * Return a borrowed book.
     */
    public function return(Book $book): JsonResponse
    {
        // Find active borrow for this user and book
        $borrow = BookBorrow::where('user_id', request()->user()->id)
            ->where('book_id', $book->id)
            ->active()
            ->first();

        if (!$borrow) {
            return $this->errorResponse(
                'You have not borrowed this book or it has already been returned',
                400
            );
        }

        try {
            DB::beginTransaction();

            // Update borrow record
            $borrow->returned_at = now();
            $borrow->save();

            // Increase available copies
            $book->incrementAvailableCopies();

            DB::commit();

            $borrow->load(['book', 'user']);

            return $this->successResponse(
                [
                    'id' => $borrow->id,
                    'book' => [
                        'id' => $borrow->book->id,
                        'title' => $borrow->book->title,
                        'author' => $borrow->book->author,
                    ],
                    'user' => [
                        'id' => $borrow->user->id,
                        'name' => $borrow->user->name,
                    ],
                    'borrowed_at' => $borrow->borrowed_at->toISOString(),
                    'due_date' => $borrow->due_date->toISOString(),
                    'returned_at' => $borrow->returned_at->toISOString(),
                    'was_overdue' => $borrow->returned_at->isAfter($borrow->due_date),
                ],
                'Book returned successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                'Failed to return book. Please try again.',
                500
            );
        }
    }
}
