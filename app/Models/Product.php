<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * These fields can be filled using create() or update() methods
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
    ];

    /**
     * The attributes that should be cast.
     * Ensures price is always returned as an integer
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'integer',
    ];
}
