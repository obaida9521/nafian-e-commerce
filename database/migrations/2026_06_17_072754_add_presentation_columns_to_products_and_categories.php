<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('tone', 9)->nullable()->after('short_description');
            $table->string('tone2', 9)->nullable()->after('tone');
            $table->string('badge', 40)->nullable()->after('tone2');
            $table->decimal('rating', 2, 1)->nullable()->after('badge');
            $table->unsignedInteger('reviews_count')->default(0)->after('rating');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->string('tone', 9)->nullable()->after('image_path');
            $table->string('tone2', 9)->nullable()->after('tone');
            $table->boolean('show_on_home')->default(false)->after('tone2');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['tone', 'tone2', 'badge', 'rating', 'reviews_count']);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn(['tone', 'tone2', 'show_on_home']);
        });
    }
};
