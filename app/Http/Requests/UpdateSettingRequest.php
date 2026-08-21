<?php

namespace App\Http\Requests;

use Override;
use App\Support\Locales;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
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
     * An emptied site name means "use APP_NAME again", so it is stored as
     * null. The color is lowercased because Theme only skips its override on
     * an exact match with the default. Merging the checkboxes makes old()
     * reflect the submitted state instead of falling back to the model.
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        $name = $this->string('site_name')->trim()->toString();

        $this->merge([
            'site_name' => $name === '' ? null : $name,
            'primary_color' => $this->string('primary_color')->trim()->lower()->toString(),
            'under_construction' => $this->boolean('under_construction'),
            'show_login_link' => $this->boolean('show_login_link'),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $available = array_keys(Locales::available());

        return [
            'site_name' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['required', 'string', 'hex_color', 'size:7'],
            'under_construction' => ['boolean'],
            'show_login_link' => ['boolean'],
            'locales' => ['required', 'array', 'min:1'],
            'locales.*' => ['string', Rule::in($available)],
            'default_locale' => ['required', 'string', Rule::in($available), 'in_array:locales.*'],
        ];
    }
}
