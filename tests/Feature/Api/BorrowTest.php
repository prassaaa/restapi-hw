<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\BookBorrow;
use App\Models\BookCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_borrow_available_book(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'total_copies' => 5,
            'available_copies' => 5,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/books/{$book->id}/borrow", [
            'due_days' => 7,
            'notes' => 'Test borrow',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Book borrowed successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'book',
                    'user',
                    'borrowed_at',
                    'due_date',
                    'notes',
                ]
            ]);

        // Check database
        $this->assertDatabaseHas('book_borrows', [
            'user_id' => $this->user->id,
            'book_id' => $book->id,
        ]);

        // Check available copies decreased
        $book->refresh();
        $this->assertEquals(4, $book->available_copies);
    }

    public function test_cannot_borrow_unavailable_book(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'total_copies' => 1,
            'available_copies' => 0, // No copies available
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/books/{$book->id}/borrow");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'This book is currently not available for borrowing',
            ]);
    }

    public function test_cannot_borrow_same_book_twice(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'total_copies' => 5,
            'available_copies' => 5,
        ]);

        // First borrow
        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'borrowed_at' => now(),
            'due_date' => now()->addDays(14),
        ]);

        // Try to borrow again
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/books/{$book->id}/borrow");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You have already borrowed this book and have not returned it yet',
            ]);
    }

    public function test_cannot_exceed_borrow_limit(): void
    {
        $category = BookCategory::factory()->create();

        // Create 5 active borrows (maximum limit)
        for ($i = 0; $i < 5; $i++) {
            $book = Book::factory()->create([
                'category_id' => $category->id,
                'available_copies' => 1,
            ]);

            BookBorrow::create([
                'user_id' => $this->user->id,
                'book_id' => $book->id,
                'borrowed_at' => now(),
                'due_date' => now()->addDays(14),
            ]);
        }

        // Try to borrow 6th book
        $newBook = Book::factory()->create([
            'category_id' => $category->id,
            'available_copies' => 1,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/books/{$newBook->id}/borrow");

        $response->assertStatus(400)
            ->assertJsonFragment([
                'success' => false,
            ]);
    }

    public function test_unauthenticated_user_cannot_borrow_book(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $response = $this->postJson("/api/books/{$book->id}/borrow");

        $response->assertStatus(401);
    }

    public function test_default_due_days_is_14(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'available_copies' => 1,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/books/{$book->id}/borrow");

        $borrow = BookBorrow::where('user_id', $this->user->id)->first();

        $this->assertNotNull($borrow);
        $this->assertEquals(
            $borrow->borrowed_at->addDays(14)->format('Y-m-d'),
            $borrow->due_date->format('Y-m-d')
        );
    }
}
