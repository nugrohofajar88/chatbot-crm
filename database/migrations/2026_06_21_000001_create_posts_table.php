<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->text('prompt')->nullable();           // instruksi operator ke AI
            $table->text('caption');                       // teks postingan final
            $table->string('image_path')->nullable();      // di public/uploads
            $table->json('platforms');                     // ['facebook','instagram']
            $table->string('status', 20)->default('draft'); // draft|published|partial|failed
            $table->string('fb_post_id')->nullable();
            $table->string('ig_post_id')->nullable();
            $table->json('result')->nullable();            // hasil/error per platform
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
