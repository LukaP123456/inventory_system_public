<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
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
            'company_id' => Company::all()->random()->id,
            'description' => $this->faker->text,
            'location' => $this->faker->countryISOAlpha3(),
            'lat' => $this->faker->latitude(),
            'long' => $this->faker->longitude(),
            'size' => $this->faker->randomFloat(1, 20, 100),
            'created_at' => now(),
            'updated_at' => now()->addDays(10)
        ];
    }
}
