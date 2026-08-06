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
        $this->assertDatabaseHas('users', ['email' => 'info@jeroendn.nl']);
    }

    public function testAdminUserCannotLoginWithoutSettingAPasswordFirst(): void
    {
        $admin = User::query()->where('email', 'info@jeroendn.nl')->firstOrFail();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $admin->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }
}
