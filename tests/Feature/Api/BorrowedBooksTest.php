<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\BookBorrow;
use App\Models\BookCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowedBooksTest extends TestCase
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

    public function test_can_list_active_borrowed_books(): void
    {
        $category = BookCategory::factory()->create();
        $book1 = Book::factory()->create(['category_id' => $category->id]);
        $book2 = Book::factory()->create(['category_id' => $category->id]);

        // Create active borrows
        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book1->id,
            'borrowed_at' => now(),
            'due_date' => now()->addDays(14),
        ]);

        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book2->id,
            'borrowed_at' => now(),
            'due_date' => now()->addDays(14),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/borrowed-books');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'book',
                        'user',
                        'borrowed_at',
                        'due_date',
                        'returned_at',
                        'is_active',
                        'is_overdue',
                    ]
                ]
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_filter_by_status_active(): void
    {
        $category = BookCategory::factory()->create();
        $book1 = Book::factory()->create(['category_id' => $category->id]);
        $book2 = Book::factory()->create(['category_id' => $category->id]);

        // Active borrow
        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book1->id,
            'borrowed_at' => now(),
            'due_date' => now()->addDays(14),
        ]);

        // Returned borrow
        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book2->id,
            'borrowed_at' => now()->subDays(14),
            'due_date' => now()->subDays(7),
            'returned_at' => now()->subDays(1),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/borrowed-books?status=active');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_status_returned(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        // Returned borrow
        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(14),
            'due_date' => now()->subDays(7),
            'returned_at' => now()->subDays(1),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/borrowed-books?status=returned');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_status_overdue(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        // Overdue borrow
        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(20),
            'due_date' => now()->subDays(6), // Overdue
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/borrowed-books?status=overdue');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertTrue($response->json('data.0.is_overdue'));
    }




    public function test_can_filter_by_user(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);
        $otherUser = User::factory()->create();

        // This user's borrow
        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'borrowed_at' => now(),
            'due_date' => now()->addDays(14),
        ]);

        // Other user's borrow
        BookBorrow::create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'borrowed_at' => now(),
            'due_date' => now()->addDays(14),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/borrowed-books?user_id=' . $this->user->id);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_my_borrows(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);
        $otherUser = User::factory()->create();

        // This user's borrow
        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'borrowed_at' => now(),
            'due_date' => now()->addDays(14),
        ]);

        // Other user's borrow
        BookBorrow::create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'borrowed_at' => now(),
            'due_date' => now()->addDays(14),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/borrowed-books?my_borrows=1');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->user->id, $response->json('data.0.user.id'));
    }

    public function test_can_filter_by_book(): void
    {
        $category = BookCategory::factory()->create();
        $book1 = Book::factory()->create(['category_id' => $category->id]);
        $book2 = Book::factory()->create(['category_id' => $category->id]);

        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book1->id,
            'borrowed_at' => now(),
            'due_date' => now()->addDays(14),
        ]);

        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book2->id,
            'borrowed_at' => now(),
            'due_date' => now()->addDays(14),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/borrowed-books?book_id=' . $book1->id);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($book1->id, $response->json('data.0.book.id'));
    }

    public function test_unauthenticated_user_cannot_list_borrowed_books(): void
    {
        $response = $this->getJson('/api/borrowed-books');

        $response->assertStatus(401);
    }
}
