<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Listing extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, HasSlug, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'status',
        'company_name',
        'description',
        'address',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
        'phone',
        'email',
        'website',
        'operating_hours',
        'keywords',
        'meta_description',
        'cover_image',
        'video_url',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'linkedin_url',
        'yelp_url',
        'is_featured',
        'is_claimed',
        'is_priority',
        'published_at',
        'featured_until',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'category_id' => 'integer',
        'operating_hours' => 'array',
        'is_featured' => 'boolean',
        'is_claimed' => 'boolean',
        'is_priority' => 'boolean',
        'published_at' => 'datetime',
        'featured_until' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /*
    |--------------------------------------------------------------------------
    | Slug
    |--------------------------------------------------------------------------
    */

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('company_name')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Resolve route binding by slug or ID.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return $this->where($field, $value)->firstOrFail();
        }

        // Try by slug first, then by ID
        return $this->where('slug', $value)
            ->orWhere('id', $value)
            ->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | Media Collections
    |--------------------------------------------------------------------------
    */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile();

        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->sharpen(10);

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ListingFaq::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function contactInquiries(): HasMany
    {
        return $this->hasMany(ContactInquiry::class);
    }

    public function claimRequests(): HasMany
    {
        return $this->hasMany(ClaimRequest::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
            ->where(function ($q) {
                $q->whereNull('featured_until')
                    ->orWhere('featured_until', '>', now());
            });
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeSearchByLocation($query, ?string $city = null, ?string $state = null, ?string $zip = null)
    {
        if ($city) {
            $query->where('city', 'like', "%{$city}%");
        }
        if ($state) {
            $query->where('state', 'like', "%{$state}%");
        }
        if ($zip) {
            $query->where('zip_code', $zip);
        }

        return $query;
    }

    public function scopeSearchByKeyword($query, ?string $keyword)
    {
        if (!$keyword) {
            return $query;
        }

        return $query->whereRaw(
            'MATCH(title, company_name, description, keywords) AGAINST(? IN BOOLEAN MODE)',
            [$keyword . '*']
        );
    }

    public function scopeInCategory($query, $categorySlug)
    {
        return $query->whereHas('category', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug)
                ->orWhereHas('parent', fn($p) => $p->where('slug', $categorySlug));
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isLive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_review';
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'active',
            'published_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'status' => 'draft',
        ]);
    }

    public function suspend(): void
    {
        $this->update([
            'status' => 'suspended',
        ]);
    }

    public function expire(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    public function averageRating(): ?float
    {
        return $this->approvedReviews()->avg('rating');
    }

    public function getPublicUrl(): string
    {
        return config('app.frontend_url') . '/listing/' . $this->slug;
    }
}
