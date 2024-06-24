<?php

namespace App\Http\Controllers;

use App\Models\AuthCheck;
use App\Models\Room;
use App\Http\Requests\StoreMagacinRequest;
use App\Http\Requests\UpdateMagacinRequest;
use http\Client\Curl\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use function Clue\StreamFilter\fun;
use function React\Promise\map;

class RoomController extends Controller
{
//    public function getUsersRooms()
//    {
//        //Get all the rooms in which the user is
//        $id = Auth::id();
//
//        $sql = DB::table('listings')
//            ->join('room_team_listings', 'room_team_listings.listing_id', '=', 'listings.id')
//            ->join('teams', 'teams.id', '=', 'room_team_listings.team_id')
//            ->join('rooms', 'rooms.id', '=', 'room_team_listings.room_id')
//            ->join('rooms as roo', 'roo.id', '=', 'room_team_listings.room_id')
//            ->join('companies', 'companies.id', '=', 'rooms.company_id')
//            ->join('user_companies', 'user_companies.company_id', '=', 'companies.id')
//            ->join('team_users', 'team_users.team_id', '=', 'teams.id')
//            ->join('users', 'users.id', '=', 'team_users.user_id')
//            ->where('users.id', '=', $id)
//            ->select(
//                'rooms.id as room_id',
//                'rooms.name as room_name',
//                'rooms.description as room_description',
//                'rooms.location as room_location',
//                'companies.co_name',
//            )
//            ->get();
//
//        return $sql;
//    }

    public function getRoomProducts(int $id)
    {
        AuthCheck::checkIfBoss();
        $room = Room::find($id);
        $sql = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->where('user_companies.company_id', '=', $room->company_id)
            ->where('user_companies.user_id', '=', $id)
            ->select(
                'companies.id as co_id'
                , 'companies.co_name'
                , 'users.first_name'
                , 'users.last_name'
                , 'users.id as user_id'
            )
            ->get();

        if ($sql->isEmpty()) {
            return response([
                'message' => "You are not in the specified company",
            ], 500);
        }

        $sql = DB::table('archive_inventories')
            ->join('rooms', 'rooms.id', '=', 'archive_inventories.room_id')
            ->join('products', 'products.id', '=', 'archive_inventories.product_id')
            ->join('users', 'users.id', '=', 'archive_inventories.user_id')
            ->where('rooms.id', '=', $id)
            ->select('archive_inventories.*', 'rooms.*', 'products.*', 'users.*')
            ->get();

        return response([
            'data' => $sql,
        ], 201);
    }

    public function getBossRooms(Request $request)
    {
        AuthCheck::checkIfBoss();
        $id = Auth::id();
        $sql = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->join('rooms', 'rooms.company_id', '=', 'companies.id')
            ->where('users.id', '=', $id)
            ->select(
                'companies.id as co_id'
                , 'companies.co_name'
                , 'rooms.id as room_id'
                , 'rooms.name as room_name'
                , 'rooms.description'
                , 'rooms.location'
                , 'rooms.size'
                , 'rooms.lat'
                , 'rooms.long'
            )
            ->get();
        return $sql;

    }

    /**
     * @return string|Collection
     */
    public function getRoom()
    {
        AuthCheck::checkIfUser();

        $user_id = Auth::id();

        $team_sql = DB::table('team_users')
            ->select('team_users.team_id')
            ->where('team_users.user_id', $user_id)
            ->get()->unique('team_id');

        $team_sql_array = json_decode(json_encode($team_sql), true);
        $room_sql = collect();

        foreach ($team_sql_array as $team_id) {
            $rooms = DB::table('room_teams')
                ->join('rooms', 'rooms.id', '=', 'room_teams.room_id')
                ->join('companies', 'companies.id', '=', 'rooms.company_id')
                ->where('room_teams.team_id', $team_id)
                ->select(
                    'rooms.id as room_id'
                    , 'rooms.name as room_name'
                    , 'rooms.description as room_description'
                    , 'rooms.location as room_location'
                    , 'companies.co_name'
                    , 'rooms.lat as latitude'
                    , 'rooms.long as longitude'
                )
                ->get();
            $room_sql = $room_sql->concat($rooms);
        }
        return $room_sql->unique('room_id');
    }

    /**
     * @param Request $request
     * @param $id
     * @return Application|ResponseFactory|Response
     */
    public function updateRoom(Request $request, $id): Response|Application|ResponseFactory
    {
        AuthCheck::checkIfBoss();
        $room = Room::find($id);
        $user_id = Auth::id();
        $sql = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->where('user_companies.company_id', '=', $room->company_id)
            ->where('user_companies.user_id', '=', $user_id)
            ->select(
                'companies.id as co_id'
                , 'companies.co_name'
                , 'users.first_name'
                , 'users.last_name'
                , 'users.id as user_id'
            )
            ->get();

        if ($sql->isEmpty()) {
            return response([
                'message' => "You are not in the specified company",
            ], 500);
        }

        if (!$room->update($request->all())) {
            $response = [
                'message' => "Failed to update room",
            ];
            return response($response, 500);
        }
        $room->refresh();
        $response = [
            'message' => "Room updated successfully",
        ];
        return response($response, 201);
    }

    /**
     * @param $id
     * @return int|string
     */
    public function deleteRoom($id): int|string
    {
        AuthCheck::checkIfBoss();

        $room = Room::find($id);
        $user_id = Auth::id();
        $sql = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->where('user_companies.company_id', '=', $room->company_id)
            ->where('user_companies.user_id', '=', $user_id)
            ->select(
                'companies.id as co_id'
                , 'companies.co_name'
                , 'users.first_name'
                , 'users.last_name'
                , 'users.id as user_id'
            )
            ->get();

        if ($sql->isEmpty()) {
            return response([
                'message' => "You are not in the specified company",
            ], 500);
        }

        if (!Room::destroy($id)) {
            return response([
                'message' => "Failed to delete room",
            ], 201);
        }
        $response = [
            'message' => "Room deleted successfully",
        ];
        return response($response, 201);

    }


    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response|string
     */
    public function createRoom(Request $request): Response|string|Application|ResponseFactory
    {
        AuthCheck::checkIfBoss();
        $id = Auth::id();
        $data = $request->validate([
            'company_id' => 'required',
            'size' => 'required',
            'name' => 'required|string|unique:rooms,name',
            'location' => 'required|string',
            'description' => 'string|required',
        ]);
        $sql = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->where('user_companies.company_id', '=', $data['company_id'])
            ->where('user_companies.user_id', '=', $id)
            ->select(
                'companies.id as co_id'
                , 'companies.co_name'
                , 'users.first_name'
                , 'users.last_name'
                , 'users.id as user_id'
            )
            ->get();
        if ($sql->isEmpty()) {
            return response([
                'message' => "You are not in the specified company",
            ], 500);
        }
        $key = 'pk.34f856643ac8ec65779afdcc67b76df5';
        $response = Http::get("https://eu1.locationiq.com/v1/search?key=" . $key . "&q=" . $data['location'] . "&format=json");
        $response_coll = $response->collect();
        $lat = $response_coll->pluck('lat');
        $long = $response_coll->pluck('lon');

        $room = Room::create([
            'company_id' => $data['company_id'],
            'size' => $data['size'],
            'name' => $data['name'],
            'location' => $data['location'],
            'lat' => $lat[0],
            'long' => $long[0],
            'description' => $data['description'],
            'created_at' => now()
        ]);

        if (empty($room)) {
            return response([
                'message' => "Failed to create room",
            ], 500);
        }
        return response([
            'message' => "Room created successfully",
        ], 201);
    }

}
