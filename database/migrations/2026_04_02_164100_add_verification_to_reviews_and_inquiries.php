<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('is_approved');
            $table->timestamp('verified_at')->nullable()->after('is_verified');
        });

        Schema::table('contact_inquiries', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('is_read');
            $table->timestamp('verified_at')->nullable()->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['is_verified', 'verified_at']);
        });

        Schema::table('contact_inquiries', function (Blueprint $table) {
            $table->dropColumn(['is_verified', 'verified_at']);
        });
    }
};
