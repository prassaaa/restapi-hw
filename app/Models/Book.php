<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

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

    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'total_copies' => 'integer',
            'available_copies' => 'integer',
        ];
    }

    /* -------------------------------------------------------------------------- */
    /* Relationships                               */
    /* -------------------------------------------------------------------------- */

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'category_id');
    }

    public function borrows(): HasMany
    {
        return $this->hasMany(BookBorrow::class, 'book_id');
    }

    /* -------------------------------------------------------------------------- */
    /* Helpers                                   */
    /* -------------------------------------------------------------------------- */

    public function hasActiveBorrows(): bool
    {
        return $this->borrows()->active()->exists();
    }

    public function isAvailable(): bool
    {
        return $this->available_copies > 0;
    }

    public function decrementAvailableCopies(): void
    {
        $this->decrement('available_copies');
    }

    public function incrementAvailableCopies(): void
    {
        $this->increment('available_copies');
    }

    /* -------------------------------------------------------------------------- */
    /* Scopes                                   */
    /* -------------------------------------------------------------------------- */

    public function scopeSearch(Builder $query, $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('author', 'like', "%{$search}%")
              ->orWhere('isbn', 'like', "%{$search}%");
        });
    }

    public function scopeAvailable(Builder $query, $isActive): Builder
    {
        if ($isActive) {
            return $query->where('available_copies', '>', 0);
        }
        return $query;
    }
}
