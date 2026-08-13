<?php

namespace App\Http\Requests;

use Override;
use App\Models\Page;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdatePageRequest extends StorePageRequest
{
    #[Override]
    protected function uniqueSlugRule(): Unique
    {
        $page = $this->route('page');
        assert($page instanceof Page);

        return Rule::unique('pages', 'slug')->ignore($page);
    }
}
