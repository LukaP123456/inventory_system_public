<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessagesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'receiver' => User::all()->random()->id,
            'sender' => User::all()->random()->id,
            'text' => $this->faker->text,
            'subject' => $this->faker->text,
            'status' => $this->faker->randomElement(['delivered','read','deleted']),
            'created_at' => now(),
            'updated_at' => now()->addDays(10)
        ];
    }
}
