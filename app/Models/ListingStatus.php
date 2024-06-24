<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static find(array $array)
 * @method static where(string $string, mixed $listing_id)
 */
class ListingStatus extends Model
{
    use HasFactory;
    protected $guarded = [];
}
