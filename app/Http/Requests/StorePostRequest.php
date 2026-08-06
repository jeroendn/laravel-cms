<?php

namespace App\Http\Requests;

use Override;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StorePostRequest extends FormRequest
{
    /**
     * The admin routes are behind the auth middleware and every
     * authenticated user is an admin, so no further check is needed.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize a manually entered slug, or generate one from the title
     * when the field was left empty.
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        $slug = $this->string('slug')->trim()->toString();

        $this->merge([
            'slug' => Str::slug($slug !== '' ? $slug : $this->string('title')->toString()),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:posts,slug'],
            'body' => ['required', 'string'],
            'published' => ['nullable', 'boolean'],
        ];
    }
}
