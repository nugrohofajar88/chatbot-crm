<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Banyak media per produk: gambar & file (PDF). Ganti image_path (tunggal)
 * dengan media (json: [{path,name,ext,is_image}]).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->json('media')->nullable()->after('description');
            if (Schema::hasColumn('listings', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('image_path')->nullable();
            $table->dropColumn('media');
        });
    }
};
