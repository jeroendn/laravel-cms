<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Data migration: bootstraps the first admin account. Registration is
// disabled, so without this row nobody could ever log in. The account is
// created WITHOUT a usable password (an unguessable random hash — the
// column is not nullable); the admin sets a real password through the
// password-reset flow (/password/reset).
return new class extends Migration {
    private const string ADMIN_EMAIL = 'info@jeroendn.nl';

    public function up(): void
    {
        $exists = DB::table('users')->where('email', self::ADMIN_EMAIL)->exists();

        if ($exists) {
            return;
        }

        DB::table('users')->insert([
            'name' => 'Jeroen',
            'email' => self::ADMIN_EMAIL,
            'password' => Hash::make(Str::random(64)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', self::ADMIN_EMAIL)->delete();
    }
};
