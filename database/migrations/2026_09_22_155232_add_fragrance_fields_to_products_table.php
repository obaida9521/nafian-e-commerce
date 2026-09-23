<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('notes_top', 255)->nullable()->after('short_description');
            $table->string('notes_heart', 255)->nullable()->after('notes_top');
            $table->string('notes_base', 255)->nullable()->after('notes_heart');
            $table->text('ingredients')->nullable()->after('notes_base');
            $table->text('usage_instructions')->nullable()->after('ingredients');
            $table->boolean('is_combo')->default(false)->after('is_featured');
            $table->boolean('hide_when_out_of_stock')->default(false)->after('is_combo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['notes_top', 'notes_heart', 'notes_base', 'ingredients', 'usage_instructions', 'is_combo', 'hide_when_out_of_stock']);
        });
    }
};
