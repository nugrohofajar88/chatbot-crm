<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('location')->nullable();
            $table->unsignedTinyInteger('beds')->default(0);
            $table->unsignedTinyInteger('baths')->default(0);
            $table->unsignedInteger('area')->default(0);       // m2
            $table->unsignedBigInteger('price')->default(0);   // Rupiah
            $table->string('status', 20)->default('available'); // available|hot|negotiation|sold
            $table->json('images')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
