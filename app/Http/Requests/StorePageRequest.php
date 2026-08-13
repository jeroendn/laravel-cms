<?php

namespace App\Http\Requests;

use Override;
use App\Rules\NotAReservedSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StorePageRequest extends FormRequest
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
     * when the field was left empty. Merging the checkboxes makes old()
     * reflect the submitted state instead of falling back to the model
     * on redirect.
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        $slug = $this->string('slug')->trim()->toString();

        $this->merge([
            'slug' => Str::slug($slug !== '' ? $slug : $this->string('title')->toString()),
            'is_draft' => $this->boolean('is_draft'),
            'show_in_menu' => $this->boolean('show_in_menu'),
        ]);

        // An ungrouped page ignores the publication date entirely; the form
        // hides the field, but a stale value could still be submitted.
        if (!$this->filled('page_group_id')) {
            $this->merge(['published_at' => null]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        // Pages and groups share the URL namespace, so a slug must be free
        // in both tables. An ungrouped page's slug becomes a first URL
        // segment and must not shadow an application route — except 'home',
        // the one root slug the application serves itself (as /).
        $slugRules = ['required', 'string', 'max:255', $this->uniqueSlugRule(), 'unique:page_groups,slug'];

        if (!$this->filled('page_group_id')) {
            $slugRules[] = new NotAReservedSlug(except: ['home']);
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => $slugRules,
            'body' => ['required', 'string'],
            'is_draft' => ['boolean'],
            'show_in_menu' => ['boolean'],
            'priority' => ['nullable', 'integer'],
            'page_group_id' => ['nullable', 'integer', Rule::exists('page_groups', 'id')],
            'published_at' => [
                Rule::requiredIf(fn(): bool => !$this->boolean('is_draft') && $this->filled('page_group_id')),
                'nullable',
                'date',
            ],
        ];
    }

    protected function uniqueSlugRule(): Unique
    {
        return Rule::unique('pages', 'slug');
    }
}
