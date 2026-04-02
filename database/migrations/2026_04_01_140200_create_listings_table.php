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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            // Core
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('status', ['draft', 'pending_review', 'active', 'suspended', 'expired'])
                ->default('draft')
                ->index();

            // Business info
            $table->string('company_name');
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('zip_code', 10);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Contact
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Hours (JSON: {"mon":{"open":"09:00","close":"17:00"}, ...})
            $table->json('operating_hours')->nullable();

            // SEO
            $table->text('keywords')->nullable();
            $table->string('meta_description')->nullable();

            // Media
            $table->string('cover_image')->nullable();
            $table->string('video_url')->nullable();

            // Social
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('yelp_url')->nullable();

            // Flags
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_claimed')->default(true);

            // Timestamps
            $table->timestamp('published_at')->nullable();
            $table->timestamp('featured_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Search index
            $table->index(['city', 'state', 'zip_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
