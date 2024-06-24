<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Listing;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class Temp_inventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'product_id' => Product::all()->random()->id,
            'user_id' => User::all()->random()->id,
            'room_id' => Room::all()->random()->id,
            'listing_id' => Listing::all()->random()->id,
            'quantity' => $this->faker->randomDigit(),
            'created_at' => now(),
            'updated_at' => now()->addDays(10)
        ];
    }
}
