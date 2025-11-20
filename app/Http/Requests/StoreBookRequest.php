<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:book_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'max:255', 'unique:books,isbn'],
            'description' => ['nullable', 'string', 'max:2000'],
            'total_copies' => ['required', 'integer', 'min:1'],
            'available_copies' => ['required', 'integer', 'min:0', 'lte:total_copies'],
            'publication_year' => ['nullable', 'integer', 'min:1000', 'max:' . (date('Y') + 1)],
            'publisher' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Category is required',
            'category_id.exists' => 'Selected category does not exist',
            'title.required' => 'Book title is required',
            'author.required' => 'Author name is required',
            'isbn.required' => 'ISBN is required',
            'isbn.unique' => 'A book with this ISBN already exists',
            'total_copies.required' => 'Total copies is required',
            'total_copies.min' => 'Total copies must be at least 1',
            'available_copies.required' => 'Available copies is required',
            'available_copies.lte' => 'Available copies cannot exceed total copies',
            'publication_year.min' => 'Publication year must be valid',
            'publication_year.max' => 'Publication year cannot be in the future',
        ];
    }
}
