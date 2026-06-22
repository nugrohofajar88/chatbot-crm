<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generalkan "listing properti" menjadi katalog produk umum.
 * title -> name; buang field properti (location/beds/baths/area/images);
 * tambah category, stock, image_path. price/description/status tetap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->renameColumn('title', 'name');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
            $table->unsignedInteger('stock')->default(0)->after('price');
            $table->string('image_path')->nullable()->after('description');
        });

        // Normalkan status lama properti -> generik.
        DB::table('listings')->whereIn('status', ['available', 'hot', 'negotiation'])->update(['status' => 'tersedia']);
        DB::table('listings')->where('status', 'sold')->update(['status' => 'habis']);

        Schema::table('listings', function (Blueprint $table) {
            if (Schema::hasColumn('listings', 'location')) {
                $table->dropColumn(['location', 'beds', 'baths', 'area']);
            }
            if (Schema::hasColumn('listings', 'images')) {
                $table->dropColumn('images');
            }
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->renameColumn('name', 'title');
            $table->string('location')->nullable();
            $table->unsignedTinyInteger('beds')->default(0);
            $table->unsignedTinyInteger('baths')->default(0);
            $table->unsignedInteger('area')->default(0);
            $table->json('images')->nullable();
            $table->dropColumn(['category', 'stock', 'image_path']);
        });
    }
};
