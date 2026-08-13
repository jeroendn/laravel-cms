<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->boolean('is_draft')->default(true)->after('body');
            $table->boolean('show_in_menu')->default(false)->after('is_draft');
            $table->integer('priority')->default(0)->after('show_in_menu');
            $table->foreignId('page_group_id')->nullable()->after('priority')
                ->constrained()->restrictOnDelete();
        });

        // Before the toggle, a page was visible iff its date had passed.
        DB::table('pages')->where('published_at', '<=', now())->update(['is_draft' => false]);
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('page_group_id');
            $table->dropColumn(['is_draft', 'show_in_menu', 'priority']);
        });
    }
};
