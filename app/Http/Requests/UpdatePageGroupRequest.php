<?php

namespace App\Http\Requests;

use Closure;
use Override;
use App\Models\PageGroup;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdatePageGroupRequest extends StorePageGroupRequest
{
    #[Override]
    protected function uniqueSlugRule(): Unique
    {
        return Rule::unique('page_groups', 'slug')->ignore($this->group());
    }

    #[Override]
    protected function validateParent(PageGroup $parent, Closure $fail): void
    {
        $group = $this->group();

        if ($parent->is($group)) {
            $fail(__('A group cannot be its own parent.'));

            return;
        }

        if ($group->children()->exists()) {
            $fail(__('This group has subgroups, so it cannot get a parent group itself.'));

            return;
        }

        parent::validateParent($parent, $fail);
    }

    private function group(): PageGroup
    {
        $group = $this->route('page_group');
        assert($group instanceof PageGroup);

        return $group;
    }
}
