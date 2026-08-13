<?php

namespace App\Http\Requests;

use Override;
use App\Models\Page;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends StorePageRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    #[Override]
    public function rules(): array
    {
        $page = $this->route('page');
        assert($page instanceof Page);

        $rules = parent::rules();
        $rules['slug'] = ['required', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($page)];

        return $rules;
    }
}
