<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes;

    protected $table = 'review';
    protected $fillable = [
        'product_id', 'name', 'email', 'title', 'content', 'is_verified',
        'score', 'image_url', 'published_at', 'rejected_at', 'search'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function images()
    {
        return $this->hasMany(ReviewImage::class);
    }

    /**
     * Get the search string for the review.
     */
    public function getSearch()
    {
        return $this->name . ';' .
            $this->email . ';' .
            $this->title . ';' .
            $this->content . ';' .
            ($this->product->name ?? '');
    }
}
