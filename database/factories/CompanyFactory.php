<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'co_name' => $this->faker->company,
            'description' => $this->faker->text,
            'created_at' => now(),
            'updated_at' => now()->addDays(10)
        ];
    }
}
