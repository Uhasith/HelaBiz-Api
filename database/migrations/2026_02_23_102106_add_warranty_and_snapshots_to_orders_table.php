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
            $table->integer('warranty_period')->nullable()->after('total');
            $table->enum('warranty_unit', ['days', 'weeks', 'months', 'years'])->nullable()->after('warranty_period');
            $table->softDeletes();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_name')->after('product_id');
            $table->string('product_sku')->nullable()->after('product_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['warranty_period', 'warranty_unit']);
            $table->dropSoftDeletes();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['product_name', 'product_sku']);
        });
    }
};
