<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
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
            'room_id' => Room::all()->random()->id,
            'quantity' => $this->faker->numberBetween(0, 100),
            'created_at' => now(),
            'updated_at' => now()->addDays(10)
        ];
    }
}
