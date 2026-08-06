<?php

namespace App\Http\Requests;

use Override;
use App\Models\Post;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends StorePostRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    #[Override]
    public function rules(): array
    {
        $post = $this->route('post');
        assert($post instanceof Post);

        $rules = parent::rules();
        $rules['slug'] = ['required', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($post)];

        return $rules;
    }
}
