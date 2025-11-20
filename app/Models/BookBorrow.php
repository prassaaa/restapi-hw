<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookBorrow extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'borrowed_at',
        'due_date',
        'returned_at',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'borrowed_at' => 'datetime',
            'due_date' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    /**
     * Get the user that borrowed the book.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the book that was borrowed.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Check if the borrow is currently active (not returned).
     */
    public function isActive(): bool
    {
        return is_null($this->returned_at);
    }

    /**
     * Check if the borrow is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->isActive() && $this->due_date->isPast();
    }

    /**
     * Scope a query to only include active borrows.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('returned_at');
    }

    /**
     * Scope a query to only include returned borrows.
     */
    public function scopeReturned($query)
    {
        return $query->whereNotNull('returned_at');
    }

    /**
     * Scope a query to only include overdue borrows.
     */
    public function scopeOverdue($query)
    {
        return $query->active()->where('due_date', '<', now());
    }
}
