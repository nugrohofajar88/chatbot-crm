<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // key -> code (sekaligus hindari kata kunci SQL `key`).
        Schema::table('settings', function (Blueprint $table) {
            $table->renameColumn('key', 'code');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->string('group', 40)->default('general')->after('code')->index();
            $table->text('description')->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex(['group']);
            $table->dropColumn(['group', 'description']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->renameColumn('code', 'key');
        });
    }
};
