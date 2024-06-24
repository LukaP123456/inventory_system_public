<?php

namespace App\Http\Controllers;

use App\Models\AuthCheck;
use App\Models\Room_team;
use App\Models\Room_team_listings;
use App\Models\Team;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team_user;
use App\Models\User;
use App\Models\User_company;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    public function getMembersOfTeam(int $team_id)
    {
        $int_team_id = (int)$team_id;
        $id = Auth::id();
        $users = DB::table('team_users')
            ->join('users', 'users.id', '=', 'team_users.user_id')
            ->where('team_users.team_id', '=', $int_team_id)
            ->whereNotIn('team_users.user_id', [$id])
            ->select(
                'team_users.id as team_users_id',
                'team_users.user_id as user_id',
                'users.first_name as first_name',
                'users.last_name as last_name',
            )
            ->get();

        return $users->unique('user_id');
    }

    public function getAllTeamsInRoom(int $room_id)
    {
        AuthCheck::checkIfBoss();
        $user_id = Auth::id();

        $sql = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->join('rooms', 'rooms.company_id', '=', 'companies.id')
            ->join('room_teams', 'room_teams.room_id', '=', 'rooms.id')
            ->join('teams', 'room_teams.team_id', '=', 'teams.id')
            ->join('companies as comp', 'comp.id', '=', 'rooms.company_id')
            ->join('users as us', 'us.id', '=', 'user_companies.user_id')
            ->where('user_companies.user_id', '=', $user_id)
            ->select(
                'companies.id as co_id'
                , 'companies.co_name'
                , 'rooms.id as room_id'
                , 'rooms.name as room_name'
                , 'teams.id as team_id'
            )
            ->get();

        $roomIds = $sql->pluck('room_id')->toArray();

//        if (!in_array($room_id, $roomIds)) {
//            return response([
//                'message' => "Invalid room id.User doesn't have access to specified room.",
//            ], 500);
//        }

        $teams_in_room = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->join('rooms', 'rooms.company_id', '=', 'companies.id')
            ->join('room_teams', 'room_teams.room_id', '=', 'rooms.id')
            ->join('teams', 'room_teams.team_id', '=', 'teams.id')
            ->join('companies as comp', 'comp.id', '=', 'rooms.company_id')
            ->join('users as us', 'us.id', '=', 'user_companies.user_id')
            ->where('rooms.id', '=', $room_id)
            ->orderBy('teams.id', 'asc')
            ->select(
                'teams.id as team_id',
                'teams.name as team_name',
                'rooms.id as room_id'
            )
            ->get()->unique('team_id');

        return \response()->json($teams_in_room);
    }

    public function deleteRoomTeam(Request $request)
    {
        AuthCheck::checkIfBoss();
        $user_id = Auth::id();
        $data = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'room_id' => 'required|exists:rooms,id',
//            'company_id' => 'required|exists:companies,id',
        ]);

        $sql = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->join('rooms', 'rooms.company_id', '=', 'companies.id')
            ->join('room_teams', 'room_teams.room_id', '=', 'rooms.id')
            ->join('teams', 'room_teams.team_id', '=', 'teams.id')
            ->join('companies as comp', 'comp.id', '=', 'rooms.company_id')
            ->join('users as us', 'us.id', '=', 'user_companies.user_id')
            ->where('user_companies.user_id', '=', $user_id)
            ->select(
                'companies.id as co_id'
                , 'companies.co_name'
                , 'rooms.id as room_id'
                , 'rooms.name as room_name'
                , 'teams.id as team_id'
            )
            ->get();


        $roomIds = $sql->pluck('room_id')->toArray();
        $teamsId = $sql->pluck('team_id')->toArray();
        $companiesId = $sql->pluck('co_id')->toArray();

        if (!in_array($data['room_id'], $roomIds)) {
            return response([
                'message' => "Invalid room id.User doesn't have access to specified room.",
            ], 500);
        }

        if (!in_array($data['team_id'], $teamsId)) {
            return response([
                'message' => "Invalid team id.User doesn't have access to specified team",
            ], 500);
        }

//        if (!in_array($data['company_id'], $companiesId)) {
//            return response([
//                'message' => "Invalid company id.User is not in specified company.",
//            ], 500);
//        }

//        $room_team = Room_team::where('id', $data['team_id'])->delete(['team_id' => $data['team_id']]);
        $room_team = Room_team::where('team_id', $data['team_id'])->where('room_id', $data['room_id'])->delete();

        if ($room_team == 0) {
            return response([
                'message' => "Failed to delete team.",
            ], 500);
        }

        return response([
            'message' => "Team deleted successfully",
        ], 201);
    }

    public function updateRoomTeam(Request $request)
    {
        AuthCheck::checkIfBoss();
        $user_id = Auth::id();
        $data = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'room_id' => 'required|exists:rooms,id',
            'company_id' => 'required|exists:companies,id',
            'description' => 'required',
        ]);

        //Sql gets all the rooms , teams and companies of the logged-in user
        $sql = DB::table('users')
            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->join('rooms', 'rooms.company_id', '=', 'companies.id')
            ->join('room_teams', 'room_teams.room_id', '=', 'rooms.id')
            ->join('teams', 'room_teams.team_id', '=', 'teams.id')
            ->join('companies as comp', 'comp.id', '=', 'rooms.company_id')
            ->join('users as us', 'us.id', '=', 'user_companies.user_id')
            ->where('user_companies.user_id', '=', $user_id)
            ->select(
                'companies.id as co_id'
                , 'companies.co_name'
                , 'rooms.id as room_id'
                , 'rooms.name as room_name'
                , 'teams.id as team_id'
            )
            ->get();


        $roomIds = $sql->pluck('room_id')->toArray();
        $teamsId = $sql->pluck('team_id')->toArray();
        $companiesId = $sql->pluck('co_id')->toArray();

        if (!in_array($data['room_id'], $roomIds)) {
            return response([
                'message' => "Invalid room id.User doesn't have access to specified room.",
            ], 500);
        }

        if (!in_array($data['team_id'], $teamsId)) {
            return response([
                'message' => "Invalid team id.User doesn't have access to specified team",
            ], 500);
        }

        if (!in_array($data['company_id'], $companiesId)) {
            return response([
                'message' => "Invalid company id.User is not in specified company.",
            ], 500);
        }

        $room_team = Room_team::where('id', $data['team_id'])->update([
            'team_id' => $data['team_id'],
            'room_id' => $data['room_id'],
            'description' => $data['description'],
        ]);

        if ($room_team == 0) {
            return response([
                'message' => "Failed to update team.",
            ], 500);
        }

        return response([
            'message' => "Updated the team successfully",
        ], 201);
    }

    public function createRoomTeam(Request $request)
    {
        AuthCheck::checkIfBoss();
        $id = Auth::id();
        $data = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'room_id' => 'required|exists:rooms,id',
            'description' => 'required',
        ]);

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
            )
            ->get();
        $roomIds = $sql->pluck('room_id')->toArray();

        if (!in_array($data['room_id'], $roomIds)) {
            return response([
                'message' => "Invalid room id",
            ], 500);
        }

        $find_room_team_duplicate = Room_team::where('team_id', $data['team_id'])
            ->where('room_id', $data['room_id'])
            ->get();

        if (count($find_room_team_duplicate) != 0) {
            return response([
                'message' => "That room team connection has already been made.",
            ], 500);
        }

        $room_team = Room_team::create($data);

        if (empty($room_team)) {
            return response([
                'message' => "Failed to add team to room",
            ], 500);
        }

        return response([
            'message' => "Added team to room",
        ], 201);
    }

    public function storeTeamUser(Request $request)
    {
        AuthCheck::checkIfBoss();

        $fields = $request->validate([
            'user_id' => 'required|exists:users,id',
            'team_id' => 'required|exists:teams,id',
        ]);

        $room_team_listing = Team_user::create([
            'user_id' => $fields['user_id'],
            'team_id' => $fields['team_id']
        ]);

        if (empty($room_team_listing)) {
            return response([
                'message' => "Failed to add user to team",
            ], 500);
        }

        return response([
            'message' => "Added user to team",
        ], 201);
    }

    public function updateTeamUser(Request $request, $id)
    {
        AuthCheck::checkIfBoss();

        $fields = $request->validate([
            'name' => 'required',
            'location' => 'required',
            'description' => 'required'
        ]);

        $team = Team_user::where('id', $id)
            ->update([
                'name' => $fields['name'],
                'location' => $fields['location'],
                'description' => $fields['description']
            ]);

        if ($team == 0) {
            return response([
                'message' => "Failed to update team.",
            ], 500);
        }

        return response([
            'message' => "Updated the team successfully",
        ], 201);
    }

    public function destroyTeamUser(int $id)
    {
        $parsed_id = (int)$id;
        $result = Team_user::destroy($parsed_id);
        if ($result == 0) {
            return response([
                'message' => "Failed to remove team",
            ], 500);
        }

        return response([
            'message' => "Team removed",
        ], 200);
    }

    public function createRoomTeamListing(Request $request)
    {
        AuthCheck::checkIfBoss();

        $fields = $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'room_id' => 'required|exists:rooms,id',
            'team_id' => 'required|exists:teams,id',
        ]);

        $room_team_listing = Room_team_listings::create([
            'listing_id' => $fields['listing_id'],
            'room_id' => $fields['room_id'],
            'team_id' => $fields['team_id']
        ]);

        if (empty($room_team_listing)) {
            $response = [
                'message' => "Failed to create a new listing",
            ];
            return response($response, 500);
        }

        $response = [
            'message' => "Created a new listing",
        ];
        return response($response, 201);
    }

    public function getTeams()
    {
        AuthCheck::checkIfBoss();
        $user_id = Auth::id();
        $company_ids_sql = User_company::where('user_id', '=', $user_id)->get();
        $company_ids = $company_ids_sql->pluck('company_id');
        $teams_sql = collect();
        foreach ($company_ids as $company_id) {
            $teams = Team::where('company_id', '=', $company_id)->get();
            $teams_sql = $teams_sql->concat($teams);
        }

        return response([
            'data' => $teams_sql,
        ], 201);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreTeamRequest $request
     * @return Response
     */
    public function store(Request $request)
    {
        AuthCheck::checkIfBoss();

        $fields = $request->validate([
            'name' => 'required',
            'location' => 'required',
            'company_id' => 'required|exists:companies,id',
            'description' => 'required'
        ]);

        $team = Team::create([
            'name' => $fields['name'],
            'location' => $fields['location'],
            'company_id' => $fields['company_id'],
            'description' => $fields['description']
        ]);

        if (empty($team)) {
            $response = [
                'message' => "Failed to create team",
            ];
            return response($response, 500);
        }

        $response = [
            'message' => "Created a new team",
        ];
        return response($response, 201);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTeamRequest $request
     * @param $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        AuthCheck::checkIfBoss();

        $fields = $request->validate([
            'name' => 'required',
            'location' => 'required',
            'company_id' => 'required|exists:companies,id',
            'description' => 'required'
        ]);

        $team = Team::where('id', $id)
            ->update([
                'name' => $fields['name'],
                'location' => $fields['location'],
                'company_id' => $fields['company_id'],
                'description' => $fields['description']
            ]);

        if ($team == 0) {
            $response = [
                'message' => "Failed to update team.",
            ];
            return response($response, 500);
        }

        $response = [
            'message' => "Updated the team successfully",
        ];
        return response($response, 201);
    }

    /**
     * @return Response|Application|ResponseFactory
     */
    public function getRoomTeamListings(): Response|Application|ResponseFactory
    {
        if (!AuthCheck::checkIfBoss()) {
            $response = [
                'message' => "You are not a boss",
            ];
            return response($response, 500);
        }

        $sql = DB::table('teams')
            ->select('rooms.*', 'listings.*')
            ->join('room_team_listings', 'room_team_listings.team_id', '=', 'teams.id')
            ->join('listings', 'listings.id', '=', 'room_team_listings.listing_id')
            ->join('rooms', 'rooms.id', '=', 'room_team_listings.room_id')
            ->get();

        $response = [
            'data' => $sql,
        ];
        return response($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Application|ResponseFactory|Response
     */
    public function destroy(int $id)
    {
        $result = Team::destroy($id);
        if ($result != 1) {
            $response = [
                'message' => "Failed to remove team",
            ];
            return response($response, 500);
        }

        $response = [
            'message' => "Team removed",
        ];
        return response($response, 201);
    }

    public function removeRoomTeamListing(int $id): Application|ResponseFactory|Response
    {
        $result = Room_team_listings::destroy($id);
        if ($result) {
            $response = [
                'message' => "Failed to remove team",
            ];
            return response($response, 500);
        }

        $response = [
            'message' => "Team removed",
        ];
        return response($response, 201);
    }

    public function updateRoomTeamListing(Request $request, $id)
    {
        if (!AuthCheck::checkIfBoss()) {
            $response = [
                'message' => "You are not a boss",
            ];
            return response($response, 500);
        }

        $fields = $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'room_id' => 'required|exists:rooms,id',
            'team_id' => 'required|exists:teams,id',
        ]);

        $room_team_listing = Room_team_listings::find($id);

        $room_team_listing->update([
            'listing_id' => $fields['listing_id'],
            'room_id' => $fields['room_id'],
            'team_id' => $fields['team_id']
        ]);

        $room_team_listing->refresh();
        $response = [
            'message' => 'Data changed'
        ];

        return \response($response, 201);
    }
}
