<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Support\OnlineUsers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->orderBy('name')
                ->simplePaginate(20),
            'onlineIds' => OnlineUsers::ids(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * New accounts get a random, undisclosed password: the site sends no
     * mail, so the admin hands over the reset link this flashes instead.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->createOrRevive([
            ...$request->safe(['name', 'email']),
            'password' => Str::password(32),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __(':Name created.', ['name' => __('user')]))
            ->with('resetLink', $this->passwordResetUrl($user));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->safe(['name', 'email']));

        return redirect()
            ->route('admin.users.index')
            ->with('status', __(':Name updated.', ['name' => __('user')]));
    }

    /**
     * A soft delete: access is gone (the auth provider and the route model
     * binding both skip trashed rows), but anything that references the
     * user keeps a row to point at.
     */
    public function destroy(User $user): RedirectResponse
    {
        // The overview hides the button on your own row; locking yourself
        // out of the admin area is never intentional.
        abort_if($user->id === Auth::id(), 403);

        $this->revokeAccess($user);
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', __(':Name deleted.', ['name' => __('user')]));
    }

    public function resetLink(User $user): RedirectResponse
    {
        return redirect()
            ->route('admin.users.index')
            ->with('resetLink', $this->passwordResetUrl($user));
    }

    /**
     * The e-mail address of a deleted account is still taken by its row, so
     * re-adding that address revives the account rather than failing on the
     * unique index. Its old password is replaced either way.
     *
     * @param array<string, mixed> $attributes
     */
    private function createOrRevive(array $attributes): User
    {
        $user = User::onlyTrashed()->firstWhere('email', $attributes['email']);

        if (!$user instanceof User) {
            return User::create($attributes);
        }

        $user->restore();
        $user->update($attributes);
        // Rows soft-deleted before delete() started revoking access carry a
        // still-valid remember-me token; clear it as they come back.
        $this->revokeAccess($user);

        return $user;
    }

    /**
     * Drop everything that still authenticates as this user besides the
     * password: the remember-me token and any active session rows. A soft
     * delete only sets deleted_at, and the guard never rechecks the password
     * on a remember-me cookie, so without this an old cookie would log the
     * account back in the moment it is revived.
     */
    private function revokeAccess(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();

        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    /**
     * The same URL Laravel would mail out. Creating a token invalidates the
     * previous one, so an earlier link stops working.
     */
    private function passwordResetUrl(User $user): string
    {
        return route('password.reset', [
            'token' => Password::createToken($user),
            'email' => $user->email,
        ]);
    }
}
