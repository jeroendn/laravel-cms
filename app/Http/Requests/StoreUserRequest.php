<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreUserRequest extends FormRequest
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
     * The password is deliberately absent: accounts are created without one
     * and the user sets theirs through the password reset link.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $this->uniqueEmail()],
        ];
    }

    /**
     * Deleted accounts keep their row, so their address is only free again
     * on paper: the controller revives such an account instead of inserting
     * a second row with the same e-mail.
     */
    protected function uniqueEmail(): Unique
    {
        return Rule::unique('users', 'email')->whereNull('deleted_at');
    }
}
