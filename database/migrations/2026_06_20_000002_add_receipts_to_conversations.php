<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Watermark dari webhook Meta: pesan keluar s.d. waktu ini sudah
            // delivered / read oleh lead. Null untuk channel tanpa receipt (WA).
            $table->timestamp('last_delivered_at')->nullable()->after('last_message_at');
            $table->timestamp('last_read_at')->nullable()->after('last_delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['last_delivered_at', 'last_read_at']);
        });
    }
};
