<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'producer' => $this->faker->company,
            'company_id' => Company::all()->random()->id,
            'description' => $this->faker->text,
            'price' => $this->faker->randomFloat(1, 20, 30),
            'created_at' => now(),
            'updated_at' => now()->addDays(10)
        ];
    }
}
