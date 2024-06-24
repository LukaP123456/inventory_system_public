<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class QRFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'path' => $this->faker->filePath(),
            'value' => $this->faker->sentence(3),
            'product_id' => Product::all()->random()->id,
            'created_at' => now(),
            'updated_at' => now()->addDays(10)
        ];
    }
}
