<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the books for the category.
     *
     * Note: This relationship will be available after Book model is created in PR #3
     */
    public function books(): HasMany
    {
        // Book model will be created in PR #3
        return $this->hasMany(\App\Models\Book::class, 'category_id');
    }
}
