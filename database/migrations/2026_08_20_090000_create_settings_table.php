<?php

use App\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('site_name')->nullable();
            $table->string('primary_color', 7)->default('#750f2e');
            $table->boolean('under_construction')->default(true);
            $table->boolean('show_login_link')->default(false);
            $table->json('locales');
            $table->string('default_locale', 5)->default('en');
            $table->timestamps();
        });

        $locale = Locales::configuredDefault();

        DB::table('settings')->insert([
            'locales' => json_encode([$locale]),
            'default_locale' => $locale,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
