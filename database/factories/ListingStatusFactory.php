<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ListingStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::all()->random()->id,
            'listing_id' => Listing::all()->random()->id,
            'room_id' => Room::all()->random()->id,
            'status' =>  $this->faker->randomElement(['finished','ongoing']),
        ];
    }
}
