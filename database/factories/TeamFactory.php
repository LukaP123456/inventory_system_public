<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name."_team",
            'location' => $this->faker->countryISOAlpha3(),
            'company_id' => Company::all()->random()->id,
            'description' => $this->faker->text,
            'created_at' => now(),
            'updated_at' => now()->addDays(10)
        ];
    }
}
