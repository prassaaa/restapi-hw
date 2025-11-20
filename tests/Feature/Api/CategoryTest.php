<?php

namespace Tests\Feature\Api;

use App\Models\Book;

use App\Models\BookCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    public function test_can_list_all_categories(): void
    {
        BookCategory::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'created_at', 'updated_at']
                ]
            ]);
    }

    public function test_can_create_category(): void
    {
        $categoryData = [
            'name' => 'Science Fiction',
            'description' => 'Books about science and future technology',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => [
                    'name' => 'Science Fiction',
                    'description' => 'Books about science and future technology',
                ]
            ]);

        $this->assertDatabaseHas('book_categories', $categoryData);
    }

    public function test_cannot_create_category_with_duplicate_name(): void
    {
        BookCategory::factory()->create(['name' => 'Fiction']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/categories', [
                'name' => 'Fiction',
                'description' => 'Another fiction category',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_name_is_required_when_creating_category(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/categories', [
                'description' => 'A category without name',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_show_single_category(): void
    {
        $category = BookCategory::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/categories/' . $category->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                ]
            ]);
    }

    public function test_can_update_category(): void
    {
        $category = BookCategory::factory()->create();

        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated description',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/categories/' . $category->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Category updated successfully',
            ]);

        $this->assertDatabaseHas('book_categories', $updateData);
    }

    public function test_can_delete_category(): void
    {
        $category = BookCategory::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/categories/' . $category->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);

        $this->assertDatabaseMissing('book_categories', ['id' => $category->id]);
    }



    public function test_cannot_delete_category_with_books(): void
    {
        $category = BookCategory::factory()->create();

        // Create a book in this category
        Book::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/categories/' . $category->id);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete category with associated books',
            ]);

        // Category should still exist
        $this->assertDatabaseHas('book_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_categories(): void
    {
        $response = $this->getJson('/api/categories');
        $response->assertStatus(401);
    }
}

