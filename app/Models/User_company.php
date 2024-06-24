<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, string $string1, int|string|null $user_id)
 * @method static firstOrCreate(array $fields)
 */
class User_company extends Model
{
    use HasFactory;
    protected $guarded=[];
}
