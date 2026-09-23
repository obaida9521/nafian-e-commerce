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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_area', 100)->nullable()->after('shipping_city');
            $table->string('shipping_postcode', 20)->nullable()->after('shipping_district');
            $table->string('delivery_zone', 20)->default('inside')->after('shipping_postcode');
            $table->string('rider_name', 100)->nullable()->after('admin_notes');
            $table->string('rider_phone', 20)->nullable()->after('rider_name');
            $table->string('payment_method', 30)->change();
            $table->index('shipping_phone');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('method', 30)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['shipping_phone']);
            $table->dropColumn(['shipping_area', 'shipping_postcode', 'delivery_zone', 'rider_name', 'rider_phone']);
        });
    }
};
