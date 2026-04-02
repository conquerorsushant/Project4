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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email_verified_at');
            $table->string('city')->nullable()->after('phone');
            $table->string('state')->nullable()->after('city');
            $table->string('zip_code', 10)->nullable()->after('state');
            $table->enum('role', ['user', 'admin'])->default('user')->after('zip_code');
            $table->boolean('is_banned')->default(false)->after('role');
            $table->timestamp('banned_at')->nullable()->after('is_banned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'city',
                'state',
                'zip_code',
                'role',
                'is_banned',
                'banned_at',
            ]);
        });
    }
};
