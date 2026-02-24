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
        Schema::table('tenants', function (Blueprint $table) {
            // Rename 'name' to 'business_name' if it exists
            if (Schema::hasColumn('tenants', 'name') && ! Schema::hasColumn('tenants', 'business_name')) {
                $table->renameColumn('name', 'business_name');
            }

            // Add missing columns if they don't exist
            if (! Schema::hasColumn('tenants', 'logo')) {
                $table->string('logo')->nullable()->after('business_name');
            }

            if (! Schema::hasColumn('tenants', 'city')) {
                $table->string('city')->nullable()->after('address');
            }

            if (! Schema::hasColumn('tenants', 'country')) {
                $table->string('country')->nullable()->after('city');
            }

            if (! Schema::hasColumn('tenants', 'currency')) {
                $table->string('currency')->default('LKR')->after('country');
            }
        });

        // Update address column type separately (Laravel requires separate alter for column type changes)
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'address')) {
                $table->text('address')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Reverse the column name change
            if (Schema::hasColumn('tenants', 'business_name') && ! Schema::hasColumn('tenants', 'name')) {
                $table->renameColumn('business_name', 'name');
            }

            // Remove added columns
            if (Schema::hasColumn('tenants', 'logo')) {
                $table->dropColumn('logo');
            }

            if (Schema::hasColumn('tenants', 'city')) {
                $table->dropColumn('city');
            }

            if (Schema::hasColumn('tenants', 'country')) {
                $table->dropColumn('country');
            }

            if (Schema::hasColumn('tenants', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};
