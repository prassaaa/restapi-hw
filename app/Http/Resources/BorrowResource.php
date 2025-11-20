<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'book' => [
                'id' => $this->book->id,
                'title' => $this->book->title,
                'author' => $this->book->author,
                'isbn' => $this->book->isbn,
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'borrowed_at' => $this->borrowed_at->toISOString(),
            'due_date' => $this->due_date->toISOString(),
            'returned_at' => $this->returned_at?->toISOString(),
            'notes' => $this->notes,
            'is_active' => $this->isActive(),
            'is_overdue' => $this->isOverdue(),
            'days_borrowed' => $this->borrowed_at->diffInDays($this->returned_at ?? now()),
        ];
    }
}
