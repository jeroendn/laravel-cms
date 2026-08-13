<?php

namespace App\Http\Requests;

use Closure;
use Override;
use App\Models\PageGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePageGroupRequest extends FormRequest
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
     * Normalize a manually entered slug, or generate one from the name when
     * the field was left empty. Merging the checkbox makes old() reflect the
     * submitted state instead of falling back to the model on redirect.
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        $slug = $this->string('slug')->trim()->toString();

        $this->merge([
            'slug' => Str::slug($slug !== '' ? $slug : $this->string('name')->toString()),
            'show_in_menu' => $this->boolean('show_in_menu'),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:page_groups,slug'],
            'show_in_menu' => ['boolean'],
            'priority' => ['nullable', 'integer'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('page_groups', 'id'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (!is_int($value) && !is_string($value)) {
                        return;
                    }

                    $parent = PageGroup::query()->find($value);

                    if ($parent !== null) {
                        $this->validateParent($parent, $fail);
                    }
                },
            ],
        ];
    }

    protected function validateParent(PageGroup $parent, Closure $fail): void
    {
        if (!$parent->isRoot()) {
            $fail(__('The parent group is itself a subgroup; only one level of nesting is allowed.'));
        }
    }
}
