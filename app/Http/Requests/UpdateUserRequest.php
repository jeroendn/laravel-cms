<?php

namespace App\Http\Requests;

use Override;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateUserRequest extends StoreUserRequest
{
    /**
     * Unlike creating, editing has no way to revive a deleted account, so
     * its address stays occupied here — the unique index counts those rows.
     */
    #[Override]
    protected function uniqueEmail(): Unique
    {
        $user = $this->route('user');
        assert($user instanceof User);

        return Rule::unique('users', 'email')->ignore($user);
    }
}
