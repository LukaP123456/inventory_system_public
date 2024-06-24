<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'start_time' => now(),
            'listing_name' => $this->faker->word,
            'company_id' => Company::all()->random()->id,
            'description' => $this->faker->text,
            'status' => $this->faker->randomElement(['idle','ongoing','finished','failed']),
            'created_at' => now(),
            'updated_at' => now()->addDays(10)
        ];
    }
}
