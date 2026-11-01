<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A product can now have several schematic drawings (e.g. front view,
     * side view, cross-section), so they move from the single
     * products.schematic_media_id column to the mediables pivot with
     * role "schematic" — the same mechanism the image gallery uses.
     */
    public function up(): void
    {
        $existing = DB::table('products')->whereNotNull('schematic_media_id')->get(['id', 'schematic_media_id']);

        foreach ($existing as $product) {
            DB::table('mediables')->insert([
                'media_id' => $product->schematic_media_id,
                'mediable_type' => 'App\\Models\\Product',
                'mediable_id' => $product->id,
                'role' => 'schematic',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('schematic_media_id');
        });
    }

    /**
     * Reverse the migrations (keeps only the first schematic per product).
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('schematic_media_id')->nullable()->after('og_image_media_id')
                ->constrained('media')->nullOnDelete();
        });

        $schematics = DB::table('mediables')
            ->where('mediable_type', 'App\\Models\\Product')
            ->where('role', 'schematic')
            ->orderBy('sort_order')
            ->get();

        foreach ($schematics->unique('mediable_id') as $row) {
            DB::table('products')->where('id', $row->mediable_id)->update(['schematic_media_id' => $row->media_id]);
        }

        DB::table('mediables')->where('mediable_type', 'App\\Models\\Product')->where('role', 'schematic')->delete();
    }
};
