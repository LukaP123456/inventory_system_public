<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class Inventory extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function products()
    {
        return $this->belongsTo(Product::class,'product_id');
    }
}
