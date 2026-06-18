<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20)->default('whatsapp');
            // Tahap pipeline: baru, terkualifikasi, viewing, negosiasi, closing
            $table->string('stage', 20)->default('baru');
            // Suhu lead: hot, warm, cold
            $table->string('temperature', 10)->default('cold');
            $table->unsignedTinyInteger('score')->default(0);
            // Toggle "ambil alih": true = AI auto-reply, false = ditangani operator
            $table->boolean('ai_enabled')->default(true);
            $table->text('summary')->nullable();           // ringkasan AI
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('unread')->default(0);
            $table->timestamps();

            $table->index('last_message_at');
            $table->index('stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
