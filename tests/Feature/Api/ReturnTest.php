<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\BookBorrow;
use App\Models\BookCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnTest extends TestCase
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

    public function test_can_return_borrowed_book(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'total_copies' => 5,
            'available_copies' => 4, // One copy borrowed
        ]);

        // Create active borrow
        $borrow = BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(7),
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/books/{$book->id}/return");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Book returned successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'book',
                    'user',
                    'borrowed_at',
                    'due_date',
                    'returned_at',
                    'was_overdue',
                ]
            ]);

        // Check database
        $borrow->refresh();
        $this->assertNotNull($borrow->returned_at);

        // Check available copies increased
        $book->refresh();
        $this->assertEquals(5, $book->available_copies);
    }

    public function test_cannot_return_book_not_borrowed(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/books/{$book->id}/return");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You have not borrowed this book or it has already been returned',
            ]);
    }

    public function test_cannot_return_already_returned_book(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        // Create already returned borrow
        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(14),
            'due_date' => now()->subDays(7),
            'returned_at' => now()->subDays(1), // Already returned
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/books/{$book->id}/return");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You have not borrowed this book or it has already been returned',
            ]);
    }

    public function test_return_detects_overdue_book(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'available_copies' => 4,
        ]);

        // Create overdue borrow
        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(20),
            'due_date' => now()->subDays(6), // Overdue
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/books/{$book->id}/return");

        $response->assertStatus(200)
            ->assertJsonPath('data.was_overdue', true);
    }

    public function test_unauthenticated_user_cannot_return_book(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $response = $this->postJson("/api/books/{$book->id}/return");

        $response->assertStatus(401);
    }
}

