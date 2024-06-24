<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Listing;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class Room_team_listingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'team_id' => Team::all()->random()->id,
            'listing_id' => Listing::all()->random()->id,
            'room_id' => Room::all()->random()->id,
            'created_at' => now(),
            'updated_at' => now()->addDays(10)
        ];
    }
}
