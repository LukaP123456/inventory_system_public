<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class Archive_inventoryFactory extends Factory
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
            'action' =>  $this->faker->randomElement(['sold','added','destroyed','return','surplus','deficit','lost','found']),
            'created_at' => now(),
            'updated_at' => now()->addDays(10)
        ];
    }
}
