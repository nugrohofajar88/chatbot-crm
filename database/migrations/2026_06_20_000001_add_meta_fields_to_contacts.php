<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Kontak Meta (Messenger/Instagram) tidak punya nomor HP.
            $table->string('phone', 30)->nullable()->change();

            // Identitas pengguna Meta: PSID (Messenger) / IGSID (Instagram).
            $table->string('psid', 64)->nullable()->after('phone');
            $table->index(['channel', 'psid']);
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['channel', 'psid']);
            $table->dropColumn('psid');
            $table->string('phone', 30)->nullable(false)->change();
        });
    }
};
