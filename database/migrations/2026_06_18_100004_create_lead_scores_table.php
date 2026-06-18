<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('budget')->default(0);
            $table->unsignedTinyInteger('engagement')->default(0);
            $table->unsignedTinyInteger('urgency')->default(0);
            $table->unsignedTinyInteger('total')->default(0);
            $table->timestamps();

            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_scores');
    }
};
