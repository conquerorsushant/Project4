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
        Schema::table('listings', function (Blueprint $table) {
            // JSON array to store services offered
            // Example: ["Service 1", "Service 2", "Service 3"]
            $table->json('services')->nullable()->after('operating_hours');

            // Boolean flag to hide business hours from public view
            $table->boolean('hide_business_hours')->default(false)->after('services');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['services', 'hide_business_hours']);
        });
    }
};
