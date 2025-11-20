<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\BookBorrow;

use App\Models\BookCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
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

    public function test_can_list_all_books(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/books');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'category_id',
                        'title',
                        'author',
                        'isbn',
                        'description',
                        'total_copies',
                        'available_copies',
                        'is_available',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    public function test_can_filter_books_by_category(): void
    {
        $category1 = BookCategory::factory()->create();
        $category2 = BookCategory::factory()->create();

        Book::factory()->count(2)->create(['category_id' => $category1->id]);
        Book::factory()->count(3)->create(['category_id' => $category2->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/books?category_id=' . $category1->id);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_search_books(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'Laravel Programming',
        ]);
        Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'PHP Basics',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/books?search=Laravel');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Laravel Programming', $response->json('data.0.title'));
    }

    public function test_can_create_book(): void
    {
        $category = BookCategory::factory()->create();

        $bookData = [
            'category_id' => $category->id,
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '978-3-16-148410-0',
            'description' => 'Test description',
            'total_copies' => 5,
            'available_copies' => 5,
            'publication_year' => 2023,
            'publisher' => 'Test Publisher',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/books', $bookData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Book created successfully',
            ])
            ->assertJsonPath('data.title', 'Test Book');

        $this->assertDatabaseHas('books', [
            'title' => 'Test Book',
            'isbn' => '978-3-16-148410-0',
        ]);
    }

    public function test_cannot_create_book_with_duplicate_isbn(): void
    {
        $category = BookCategory::factory()->create();
        $existingBook = Book::factory()->create([
            'category_id' => $category->id,
            'isbn' => '978-3-16-148410-0',
        ]);

        $bookData = [
            'category_id' => $category->id,
            'title' => 'Another Book',
            'author' => 'Another Author',
            'isbn' => '978-3-16-148410-0', // Duplicate ISBN
            'total_copies' => 3,
            'available_copies' => 3,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/books', $bookData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    public function test_required_fields_when_creating_book(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/books', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'title', 'author', 'isbn', 'total_copies', 'available_copies']);
    }

    public function test_can_show_single_book(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/books/' . $book->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Book retrieved successfully',
            ])
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', $book->title);
    }

    public function test_can_update_book(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $updateData = [
            'category_id' => $category->id,
            'title' => 'Updated Title',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'description' => 'Updated description',
            'total_copies' => 10,
            'available_copies' => 8,
            'publication_year' => 2024,
            'publisher' => 'Updated Publisher',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/books/' . $book->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Book updated successfully',
            ])
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_book(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson('/api/books/' . $book->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Book deleted successfully',
            ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_cannot_delete_book_with_active_borrows(): void
    {
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'available_copies' => 4,
        ]);

        // Create active borrow
        BookBorrow::create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'borrowed_at' => now(),
            'due_date' => now()->addDays(14),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson('/api/books/' . $book->id);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete book with active borrows. Please wait until all copies are returned.',
            ]);

        // Book should still exist
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_books(): void
    {
        $response = $this->getJson('/api/books');

        $response->assertStatus(401);
    }

    public function test_available_copies_cannot_exceed_total_copies(): void
    {
        $category = BookCategory::factory()->create();

        $bookData = [
            'category_id' => $category->id,
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '978-3-16-148410-0',
            'total_copies' => 5,
            'available_copies' => 10, // More than total
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/books', $bookData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['available_copies']);
    }
}
