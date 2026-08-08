<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestsAreRedirectedToLogin(): void
    {
        $user = User::factory()->create();

        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->get(route('admin.users.create'))->assertRedirect(route('login'));
        $this->post(route('admin.users.store'))->assertRedirect(route('login'));
        $this->get(route('admin.users.edit', $user))->assertRedirect(route('login'));
        $this->put(route('admin.users.update', $user))->assertRedirect(route('login'));
        $this->delete(route('admin.users.destroy', $user))->assertRedirect(route('login'));
        $this->post(route('admin.users.reset-link', $user))->assertRedirect(route('login'));
    }

    public function testIndexListsTheAccounts(): void
    {
        User::factory()->create(['name' => 'Client editor', 'email' => 'editor@example.test']);

        $response = $this->actingAs($this->admin())->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Client editor');
        $response->assertSee('editor@example.test');
    }

    public function testAdminCanViewTheCreateAndEditForms(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Existing user']);

        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk();

        $edit = $this->actingAs($admin)->get(route('admin.users.edit', $user));
        $edit->assertOk();
        $edit->assertSee('Existing user');
    }

    public function testAdminCanCreateAUserWithoutAUsablePassword(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New editor',
            'email' => 'new@example.test',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('resetLink');
        $this->assertDatabaseHas('users', ['name' => 'New editor', 'email' => 'new@example.test']);

        $created = User::query()->where('email', 'new@example.test')->firstOrFail();
        $this->assertSame('bcrypt', Hash::info($created->password)['algoName']);

        // The link is only handed over on screen; nothing is mailed.
        $link = $this->resetLink();

        $overview = $this->actingAs($admin)->get(route('admin.users.index'));
        $overview->assertSee(__('Password reset link'));
        $overview->assertSee($link);
    }

    public function testTheGeneratedLinkLetsTheNewUserSetAPassword(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'New editor',
            'email' => 'new@example.test',
        ]);

        $link = $this->resetLink();

        $this->get($link)->assertOk();

        $response = $this->post(route('password.update'), [
            'token' => $this->tokenFrom($link),
            'email' => 'new@example.test',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $user = User::query()->where('email', 'new@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('a-brand-new-password', $user->password));
    }

    public function testAdminCanGenerateAResetLinkForAnExistingUser(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin())->post(route('admin.users.reset-link', $user));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertStringContainsString('/password/reset/', $this->resetLink());
    }

    public function testNameAndUniqueEmailAreValidated(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $response = $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => '',
            'email' => 'taken@example.test',
        ]);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    public function testAnUpdateCannotTakeOverTheAddressOfADeletedAccount(): void
    {
        User::factory()->create(['email' => 'gone@example.test'])->delete();
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin())->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => 'gone@example.test',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function testAdminCanUpdateAUserKeepingItsOwnEmail(): void
    {
        $user = User::factory()->create(['email' => 'editor@example.test']);

        $response = $this->actingAs($this->admin())->put(route('admin.users.update', $user), [
            'name' => 'Renamed editor',
            'email' => 'editor@example.test',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSame('Renamed editor', $user->refresh()->name);
    }

    public function testDeletingAUserKeepsTheRow(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin())->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSoftDeleted($user);
    }

    public function testDeletingAUserRevokesTheRememberTokenAndActiveSessions(): void
    {
        $user = User::factory()->create(['remember_token' => 'old-remember-token']);
        DB::table('sessions')->insert([
            'id' => Str::random(40),
            'user_id' => $user->id,
            'payload' => '',
            'last_activity' => now()->getTimestamp(),
        ]);

        $this->actingAs($this->admin())->delete(route('admin.users.destroy', $user));

        // A leftover remember-me cookie must no longer match, and the session
        // row that could resume the login is gone.
        $reloaded = User::withTrashed()->findOrFail($user->id);
        $this->assertNotSame('old-remember-token', $reloaded->remember_token);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    }

    public function testADeletedUserCannotLogIn(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password', // UserFactory default
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function testADeletedUserIsGoneFromTheAdminArea(): void
    {
        $user = User::factory()->create(['name' => 'Former editor']);
        $user->delete();

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertDontSee('Former editor');
        $this->actingAs($admin)->get(route('admin.users.edit', $user))->assertNotFound();
        $this->actingAs($admin)->post(route('admin.users.reset-link', $user))->assertNotFound();
    }

    public function testADeletedUserGetsNoPasswordResetMail(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->delete();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertNothingSent();
    }

    public function testReAddingADeletedAddressRevivesTheAccount(): void
    {
        $user = User::factory()->create(['name' => 'Former editor', 'email' => 'editor@example.test']);
        $user->delete();

        $response = $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Returning editor',
            'email' => 'editor@example.test',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSame(1, User::query()->where('email', 'editor@example.test')->count());

        $revived = $user->fresh();
        $this->assertNotNull($revived);
        $this->assertNull($revived->deleted_at);
        $this->assertSame('Returning editor', $revived->name);
        // Reviving does not hand back the old password; the fresh link does.
        $this->assertFalse(Hash::check('password', $revived->password));
    }

    public function testRevivingRotatesARememberTokenLeftByAnOlderDelete(): void
    {
        // A model-level soft delete leaves the token untouched, standing in
        // for a row deleted before destroy() started revoking access.
        $user = User::factory()->create(['email' => 'editor@example.test', 'remember_token' => 'stale-token']);
        $user->delete();

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Returning editor',
            'email' => 'editor@example.test',
        ]);

        $this->assertNotSame('stale-token', User::findOrFail($user->id)->remember_token);
    }

    public function testAdminCannotDeleteTheirOwnAccount(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function testTheOverviewOffersNoDeleteButtonForYourOwnAccount(): void
    {
        $admin = $this->admin();
        $other = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertSee('action="' . route('admin.users.destroy', $other) . '"', false);
        $response->assertDontSee('action="' . route('admin.users.destroy', $admin) . '"', false);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    /**
     * The reset link the last request flashed to the session.
     */
    private function resetLink(): string
    {
        $link = session('resetLink');
        $this->assertIsString($link);

        return $link;
    }

    private function tokenFrom(string $link): string
    {
        $path = parse_url($link, PHP_URL_PATH);
        $this->assertIsString($path);

        return basename($path);
    }
}
