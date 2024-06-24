<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class Room_teamFactory extends Factory
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
            'room_id' => Room::all()->random()->id,
        ];
    }
}
