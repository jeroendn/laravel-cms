<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Registration is disabled, so without this row nobody could ever log in.
// The random password hash is deliberate (the column is not nullable) — the
// admin gets in through the password-reset flow.
return new class extends Migration {
    public function up(): void
    {
        $email = config('app.admin_email');

        if (! is_string($email) || $email === '') {
            throw new RuntimeException('Set ADMIN_EMAIL in .env before migrating — it seeds the first admin account.');
        }

        $exists = DB::table('users')->where('email', $email)->exists();

        if ($exists) {
            return;
        }

        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', config('app.admin_email'))->delete();
    }
};
