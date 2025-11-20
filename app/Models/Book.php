<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'title',
        'author',
        'isbn',
        'description',
        'total_copies',
        'available_copies',
        'publication_year',
        'publisher',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'total_copies' => 'integer',
            'available_copies' => 'integer',
        ];
    }

    /**
     * Get the category that owns the book.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'category_id');
    }

    /**
     * Get the borrows for the book.
     */
    public function borrows(): HasMany
    {
        return $this->hasMany(BookBorrow::class, 'book_id');
    }

    /**
     * Check if the book is available for borrowing.
     */
    public function isAvailable(): bool
    {
        return $this->available_copies > 0;
    }

    /**
     * Decrease available copies when borrowed.
     */
    public function decrementAvailableCopies(): void
    {
        $this->decrement('available_copies');
    }

    /**
     * Increase available copies when returned.
     */
    public function incrementAvailableCopies(): void
    {
        $this->increment('available_copies');
    }
}
