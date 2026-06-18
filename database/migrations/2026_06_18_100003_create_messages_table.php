<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 3);                // in | out
            $table->string('sender', 10);                  // lead | operator | ai
            $table->text('body')->nullable();
            $table->string('type', 20)->default('text');   // text | image | dst
            $table->json('payload')->nullable();           // payload mentah webhook
            $table->string('wa_message_id')->nullable();   // id pesan WA (dedup)
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
