<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function testMigrationsCreateTheAdminUser(): void
    {
        $this->assertDatabaseHas('users', ['email' => config('app.admin_email')]);
    }

    public function testAdminUserCannotLoginWithoutSettingAPasswordFirst(): void
    {
        $admin = User::query()->where('email', config('app.admin_email'))->firstOrFail();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $admin->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }
}
