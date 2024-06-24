<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static find(int $id)
 * @method static where(string $string, string $string1, string $string2)
 * @method static create(array $all)
 */
class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function temp_inventories()
    {
        return $this->hasMany(Temp_inventory::class);
    }

}
