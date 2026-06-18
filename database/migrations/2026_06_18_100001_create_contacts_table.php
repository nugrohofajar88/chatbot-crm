<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone', 30)->unique();        // nomor WA
            $table->string('email')->nullable();
            $table->string('channel', 20)->default('whatsapp');
            $table->json('prefs')->nullable();             // preferensi lead (tag)
            $table->timestamp('lead_since')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
