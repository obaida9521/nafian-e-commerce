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
        // Images uploaded straight into the media library (the file itself lives in Spatie's `media` table).
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('title', 191)->nullable();
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
