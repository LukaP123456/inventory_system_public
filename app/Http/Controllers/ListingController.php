<?php

namespace App\Http\Controllers;

use App\Models\Archive_inventory;
use App\Models\AuthCheck;
use App\Models\Inventory;
use App\Models\Listing;
use App\Http\Requests\StorePopisRequest;
use App\Http\Requests\UpdatePopisRequest;
use App\Models\ListingStatus;
use App\Models\Messages;
use App\Models\Room;
use App\Models\Room_team_listings;
use App\Models\Temp_inventory;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use function Clue\StreamFilter\fun;
use function React\Promise\map;
use function Sodium\add;

class ListingController extends Controller
{
    public function endListing(Request $request)
    {
        AuthCheck::checkIfBoss();

        $data = $request->validate([
            'listing_id' => 'required|exists:listings,id',
//            'room_id' => 'required|exists:rooms,id'
        ]);

        $listing [] = Listing::where('id', $data['listing_id'])->update(['status' => 'finished']);

        $temp_inventories = Temp_inventory::with('products')->where('listing_id', $data['listing_id'])->get();
        $message = [];
        if ($temp_inventories->isEmpty()) {
            $message['temp_invent_message'] = ['Temp inventory table empty'];
        }

        $rooms = Room_team_listings::where('listing_id', '=', $data['listing_id'])->get();
        $room_ids = $rooms->pluck('room_id');

//        $inventories = Inventory::with('products')->where('room_id', $data['room_id'])->get();
        foreach ($room_ids as $room_id) {
            $inventories = Inventory::with('products')->where('room_id', $room_id)->get();
        }

        if ($inventories->isEmpty() and $temp_inventories->isEmpty()) {
            $message ['invent_message'] = ['Inventory table empty'];
        }

        if (!empty($message)) {
            return \response($message, 200);
        }

        $different_temp = [];
        $different_quant = [];
        for ($i = 0; $i < $temp_inventories->count(); $i++) {
            $found = false;
            for ($j = 0; $j < $inventories->count(); $j++) {
                if ($inventories[$j]['product_id'] == $temp_inventories[$i]['product_id'] and $inventories[$j]['room_id'] == $temp_inventories[$i]['room_id']) {
                    $found = true;
                    if ($inventories[$j]['quantity'] != $temp_inventories[$i]['quantity']) {
                        $different_quant [] = $temp_inventories[$i];
                    }
                }
            }
            if (!$found) {
                //Ovde su oni kojih nema u inventories
                $different_temp [] = $temp_inventories[$i];
            }
        }

        //$different_temp treba da se ubaci u inventories tabelu i izbrise iz temp_invnetories tabele
        if (!empty($different_temp) or !empty($different_quant)) {
            $inventory_add_result = 0;
            $temp_inventories_delete_result = 0;
            $items = array_merge($different_temp, $different_quant);
            foreach ($items as $item) {
                $inventory_add_result = Inventory::create([
                    'product_id' => $item['product_id'],
                    'room_id' => $item['room_id'],
                    'quantity' => $item['quantity'],
                    'created_at' => now(),
                    'updated_at' => null
                ]);

                $archive_invent_add_result = Archive_inventory::create([
                    'product_id' => $item['product_id'],
                    'room_id' => $item['room_id'],
                    'action' => 'sold',
                    'created_at' => now(),
                    'updated_at' => null
                ]);

                $temp_inventories_delete_result = Temp_inventory::where([
                    'room_id' => $item['room_id'],
                ])->delete();
            }
            if ($inventory_add_result->count() > 0 and $temp_inventories_delete_result > 0) {
                $message['add'] = 'Added new data to inventory table';
            }
        }


        $different_invent = [];
        for ($i = 0; $i < $inventories->count(); $i++) {
            $found = false;
            for ($j = 0; $j < $temp_inventories->count(); $j++) {
                if ($inventories[$i]['product_id'] == $temp_inventories[$j]['product_id'] and $inventories[$i]['room_id'] == $temp_inventories[$j]['room_id']) {
                    $found = true;
                }
            }
            if (!$found) {
                //Ovde su oni kojih nema u temp_inventories
                $different_invent [] = $inventories[$i];
            }
        }
        //$different_invent treba da se izbaci iz inventories i stavi u archive_inventories
        if (!empty($different_invent)) {
            $inventory_delete_result = 0;
            $archive_inventories_add_result = 0;
            foreach ($different_invent as $item) {
                $archive_inventories_add_result = Archive_inventory::create([
                    'product_id' => $item['product_id'],
                    'room_id' => $item['room_id'],
                    'action' => 'sold',
                    'created_at' => now(),
                    'updated_at' => null
                ]);
                $inventory_delete_result = Inventory::where([
                    'room_id' => $item['room_id'],
                ])->delete();
            }
            if ($archive_inventories_add_result->count() > 0 and $inventory_delete_result > 0) {
                $message['add'] = 'Added new data to inventory table';
            }
        }

        if (empty($different_invent) and empty($different_temp) and empty($different_quant)) {
            $message['empty'] = 'No difference between listing and inventory';
        }

        if (isset($message['empty'])) {
            return \response([
                'message' => $message['empty']
            ], 200);
        }

        if (isset($message['add']) and count($listing) > 0) {
            return \response([
                'message' => 'New products added to inventory'
            ], 200);
        }
        return \response([
            'message' => 'Failed to add new products to inventory'
        ], 500);
    }

    public function getDifference(Request $request)
    {
        AuthCheck::checkIfBoss();

        $data = $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'room_id' => 'required|exists:rooms,id'
        ]);

        $temp_inventories = Temp_inventory::with('products')->where('listing_id', $data['listing_id'])->get();
        $message = [];
        if ($temp_inventories->isEmpty()) {
            $message['temp_invent_message'] = ['Temp inventory table empty'];
        }
        $inventories = Inventory::with('products')->where('room_id', $data['room_id'])->get();
        if ($inventories->isEmpty()) {
            $message ['invent_message'] = ['Inventory table empty'];
        }

        if (!empty($message)) {
            return \response($message, 200);
        }

        $different_temp = [];
        $different_quant = [];
        for ($i = 0; $i < $temp_inventories->count(); $i++) {
            $found = false;
            for ($j = 0; $j < $inventories->count(); $j++) {
                if ($inventories[$j]['product_id'] == $temp_inventories[$i]['product_id'] and $inventories[$j]['room_id'] == $temp_inventories[$i]['room_id']) {
                    $found = true;
                    if ($inventories[$j]['quantity'] != $temp_inventories[$i]['quantity']) {
                        $different_quant [] = $temp_inventories[$i];
                    }
                }
            }
            if (!$found) {
                //Ovde su oni kojih nema u inventories
                $different_temp [] = $temp_inventories[$i];
            }
        }

        $different_invent = [];
        $different_invent_quant = [];
        for ($i = 0; $i < $inventories->count(); $i++) {
            $found = false;
            for ($j = 0; $j < $temp_inventories->count(); $j++) {
                if ($inventories[$i]['product_id'] == $temp_inventories[$j]['product_id'] and $inventories[$i]['room_id'] == $temp_inventories[$j]['room_id']) {
                    $found = true;
                    if ($inventories[$i]['quantity'] != $temp_inventories[$j]['quantity']) {
                        $different_invent_quant [] = $inventories[$i];
                    }
                }
            }
            if (!$found) {
                //Ovde su oni kojih nema u temp_inventories
                $different_invent [] = $inventories[$i];
            }
        }

        $different_invent_collection = collect($different_invent);
        $different_invent_quant_collection = collect($different_invent_quant);

        $different_temp_collection = collect($different_temp);
        $different_temp_quant_collection = collect($different_quant);
        $result = [];

        //If temp inventory is empty and inventory is not that means that the user didn't find the products which are in the inventory table.
        //This means that the inventory table contains products which don't exist anymore in the room.
        if ($different_invent_collection->isEmpty() and !$different_temp_collection->isEmpty() and $different_invent_quant_collection->isEmpty() and $different_temp_quant_collection->isEmpty()) {
            $result = $different_invent_collection->concat($different_temp_collection->map(function ($item) {
                $item['quantity'] = -1 * $item['quantity'];
                return $item;
            }));

            $result = $result->map(function ($item) {
                return [
                    'quantity' => $item['quantity'],
                    'name' => $item['products']['name'],
                    'producer' => $item['products']['producer'],
                    'description' => $item['products']['description'],
                    'price' => $item['products']['price'],
                ];
            });

            return \response([
                'data1' => $result
            ]);
        }
        //This is the reverse of the code above. It will always return positive data which means that the inventory table doesn't contain the returned data
        if (!$different_invent_collection->isEmpty() and $different_temp_collection->isEmpty() and $different_invent_quant_collection->isEmpty() and $different_temp_quant_collection->isEmpty()) {
            $result = $different_invent_collection->concat($different_temp_collection->map(function ($item) {
                return $item;
            }));

            $result = $result->map(function ($item) {
                return [
                    'quantity' => $item['quantity'],
                    'name' => $item['products']['name'],
                    'producer' => $item['products']['producer'],
                    'description' => $item['products']['description'],
                    'price' => $item['products']['price'],
                ];
            });

            return \response([
                'data2' => $result
            ]);
        }

        //Both of the collections are empty
        if ($different_invent_collection->isEmpty() and $different_temp_collection->isEmpty()) {
            return response([
                'message' => 'No difference between listing and inventory'
            ], 200);
        }

        $result = $different_invent_collection->concat($different_temp_collection);

        $new_collection = $inventories->map(function ($item) use ($temp_inventories) {
            $temp_item = $temp_inventories->where('product_id', '==', $item['product_id'])
                ->where('room_id', '==', $item['room_id'])
                ->first();
            if ($temp_item and $temp_item->quantity != $item['quantity']) {
                $item['quantity'] = $temp_item['quantity'] - $item['quantity'];
                return $item;
            } else if (!$temp_item) {
                $item['quantity'] = -1 * $item['quantity'];
                return $item;
            }
        });

        $result = $result->concat($new_collection->whereNotNull());
        $result = $result->unique();

        $result = $result->map(function ($item) {
            return [
                'quantity' => $item['quantity'],
                'name' => $item['products']['name'],
                'producer' => $item['products']['producer'],
                'description' => $item['products']['description'],
                'price' => $item['products']['price'],
            ];
        });

        return response([
            'data' => $result,
        ], 200);
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response|void
     */
    public
    function finishListings4Rooms(Request $request)
    {
        AuthCheck::checkIfBoss();

        $data = $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'room_id' => 'required|exists:rooms,id',
        ]);

        $listing_statuses = ListingStatus::where('listing_id', $data['listing_id'])->where('room_id', $data['room_id'])->get();

        if ($listing_statuses->pluck('status')->every(fn($status) => $status === 'finished')) {
            $listings = Listing::where('id', $data['listing_id'])->firstOrfail();
            if ($listings->update(['status' => 'finished', 'updated_at' => now()])) {
                return response(['data' => 'Listing ' . $listings->listing_name . ' finished'], 200);
            }
        } else {
            return response(['data' => 'Not all users have finished their listings'], 200);
        }
    }

    /**
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public
    function finishListingInRoom(Request $request): Response|Application|ResponseFactory
    {
        AuthCheck::checkIfBoss();

        $data = $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'room_id' => 'required|exists:rooms,id',
        ]);

        try {
            $listing = ListingStatus::where('listing_id', $data['listing_id'])->where('room_id', $data['room_id']);
            if ($listing->update(['status' => 'finished', 'updated_at' => now()])) {
                return \response([
                    "data" => "Listing status set to finished",
                ], 200);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return \response([
                "data" => "No record found to update",
            ], 500);
        }

        return \response([
            "data" => "Failed to finish listing",
        ], 500);
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public
    function unFinishListingInRoom(Request $request): Response|Application|ResponseFactory
    {
        AuthCheck::checkIfBoss();

        $data = $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'room_id' => 'required|exists:rooms,id',
        ]);

        try {
            $listing = ListingStatus::where('listing_id', $data['listing_id'])->where('room_id', $data['room_id']);
            if ($listing->update(['status' => 'ongoing', 'updated_at' => now()])) {
                return \response([
                    "data" => "Listing status set to ongoing",
                ], 200);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return \response([
                "data" => "No record found to update",
            ], 500);
        }

        return \response([
            "data" => "Failed to unfinish listing",
        ], 500);
    }

//    /**
//     * @param Request $request
//     * @return Collection
//     */
//    public
//    function getDifference(Request $request): Collection
//    {
//        $data = $request->validate([
//            'room_id' => 'required|exists:rooms,id',
//            'listing_id' => 'required|exists:listings,id'
//        ]);
//
//        $total_difference = DB::table('temp_inventories')
//            ->join('inventories', 'inventories.room_id', '=', 'temp_inventories.room_id')
//            ->where('temp_inventories.room_id', $data['room_id'])
//            ->where('temp_inventories.listing_id', $data['listing_id'])
//            ->select(
//                'inventories.quantity as inventory_quantity',
//                'temp_inventories.quantity as temp_inventory_quantity',
//            )
//            ->get();
//
//        $result = $total_difference->map(function ($item) {
//            $difference = $item->inventory_quantity - $item->temp_inventory_quantity;
//            return [
//                'inventory_quantity' => $item->inventory_quantity,
//                'temp_inventory_quantity' => $item->temp_inventory_quantity,
//                'difference' => $difference,
//            ];
//        });
//
//        return $result;
//    }

    /**
     * @param int $listing_id
     * @return Application|ResponseFactory|Response
     */
    public
    function getRoomsInListing(int $listing_id): Response|Application|ResponseFactory
    {
        AuthCheck::checkIfBoss();
        $sql = DB::table('listing_statuses')
            ->join('rooms', 'rooms.id', '=', 'listing_statuses.room_id')
            ->where('listing_statuses.listing_id', '=', $listing_id)
            ->select(
                'listing_statuses.room_id',
                'listing_statuses.user_id',
                'listing_statuses.status',
                'rooms.name',
                'rooms.id',
                'rooms.description',
                'rooms.location',
            )
            ->get()->unique('room_id');

        $total_no_users = $sql->count('user_id');

        $total_no_finished_users = $sql->filter(function ($item) {
            return $item->status === 'finished';
        })->count();

        $temp_inventories_query = Temp_inventory::where('listing_id', '=', $listing_id)->get();

        $sum_of_rooms = $temp_inventories_query->groupBy('room_id')->map(function ($item) {
            return $item->sum('quantity');
        });

        $room_ids = $sql->pluck('room_id')->unique();
        $inventories_sql = [];
        foreach ($room_ids as $room_id) {
            $temp = Inventory::where('room_id', '=', $room_id)->get();
            $inventories_sql[] = $temp;
        }

        $quantity = collect($inventories_sql)->flatten()->groupBy('room_id')->map(function ($item) {
            return $item->sum('quantity');
        });

        $sql = $sql->map(function ($item) use ($total_no_users, $total_no_finished_users, $sum_of_rooms, $quantity) {
            $item->total_no_users = $total_no_users;
            $item->total_no_finished_user_ids = $total_no_finished_users;
            $item->temp_inventories_quantity = $sum_of_rooms->get($item->room_id);
            $item->inventories_quantity = $quantity->get($item->room_id);
            return $item;
        });

        return \response([
            "data" => $sql,
        ], 200);
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public
    function createRoomTeamListing(Request $request): Response|Application|ResponseFactory
    {
        AuthCheck::checkIfBoss();
        $id = Auth::id();

        $data = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'room_id' => 'required|exists:rooms,id',
            'listing_id' => 'required|exists:listings,id',
        ]);

        $room_team_listing = Room_team_listings::firstOrCreate([
            'team_id' => $data['team_id'],
            'room_id' => $data['room_id'],
            'listing_id' => $data['listing_id'],
        ]);

        $sql = DB::table('team_users')
            ->join('teams', 'teams.id', '=', 'team_users.team_id')
            ->where('team_users.team_id', '=', $data['team_id'])
            ->select('team_users.user_id')
            ->get();

        $user_ids = $sql->pluck('user_id');

        $exists = ListingStatus::where('listing_id', $data['listing_id'])
            ->where('room_id', $data['room_id'])
            ->whereIn('user_id', $user_ids)
            ->exists();

        if ($exists) {
            return response([
                'message' => "Listing created already.",
            ], 500);
        }

        $records = $user_ids->map(function ($id) use ($data) {
            return [
                'listing_id' => $data['listing_id'],
                'room_id' => $data['room_id'],
                'user_id' => $id,
                'status' => 'ongoing',
                'created_at' => now()
            ];
        });

        ListingStatus::insert($records->toArray());

        return response([
            'message' => "Room team listing created",
        ], 200);
    }

    public
    function deleteRoomTeamListings(int $listing_id)
    {
        AuthCheck::checkIfBoss();

        $room_team_listing = Room_team_listings::find($listing_id);
        $delete_status = ListingStatus::where('listing_id', $room_team_listing->listing_id)
            ->where('room_id', $room_team_listing->room_id)
            ->delete();
        $result = $room_team_listing->delete();

        if ($result == 0 or $delete_status == 0) {
            return \response("Failed", 500);
        }
        return \response("Deleted", 200);

    }

    public
    function getRoomTeamListings(int $listing_id)
    {
        AuthCheck::checkIfBoss();

        $sql = DB::table('room_team_listings')
            ->join('listings', 'listings.id', '=', 'room_team_listings.listing_id')
            ->join('teams', 'teams.id', '=', 'room_team_listings.team_id')
            ->join('rooms', 'rooms.id', '=', 'room_team_listings.room_id')
            ->where('listings.id', '=', $listing_id)
            ->select(
                'room_team_listings.id',
                'teams.name as team_name',
                'rooms.name as room_name',
            )
            ->get();

        return \response($sql, 200);
    }

    public
    function getListingStatuses(Request $request)
    {
        AuthCheck::checkIfUser();
        $id = Auth::id();//1
        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'listing_id' => 'required|exists:listings,id',
        ]);

        //Sql gets all the rooms , teams and companies of the logged-in user
        $sql = DB::table('listings')
            ->join('room_team_listings', 'room_team_listings.listing_id', '=', 'listings.id')
            ->join('teams', 'teams.id', '=', 'room_team_listings.team_id')
            ->join('rooms', 'rooms.id', '=', 'room_team_listings.room_id')
            ->join('rooms as roo', 'roo.id', '=', 'room_team_listings.room_id')
            ->join('companies', 'companies.id', '=', 'rooms.company_id')
            ->join('user_companies', 'user_companies.company_id', '=', 'companies.id')
            ->join('team_users', 'team_users.team_id', '=', 'teams.id')
            ->join('users', 'users.id', '=', 'team_users.user_id')
            ->where('users.id', '=', $id)
            ->select('rooms.id as room_id',
                'rooms.id as room_id',
                'listings.id as listing_id',
            )
            ->get();

        $roomIds = $sql->pluck('room_id')->toArray();
        $listingIds = $sql->pluck('listing_id')->toArray();

        if (!in_array($data['room_id'], $roomIds)) {
            return response([
                'message' => "Invalid room id.User doesn't have access to specified room.",
            ], 500);
        }

        if (!in_array($data['listing_id'], $listingIds)) {
            return response([
                'message' => "Invalid listing id.User doesn't have access to specified listing.",
            ], 500);
        }

        $status_sql = DB::table('listing_statuses')
            ->join('listings', 'listings.id', '=', 'listing_statuses.listing_id')
            ->join('users', 'users.id', '=', 'listing_statuses.user_id')
            ->where('listing_statuses.room_id', $data['room_id'])
            ->where('listing_statuses.listing_id', $data['listing_id'])
            ->select(
                'listing_statuses.*',
                'users.first_name',
                'users.last_name'
            )
            ->get();

        $users_count = count($status_sql->pluck('user_id')->toArray());

        $status_sql2 = DB::table('listing_statuses')
            ->join('listings', 'listings.id', '=', 'listing_statuses.listing_id')
            ->join('users', 'users.id', '=', 'listing_statuses.user_id')
            ->where('listing_statuses.room_id', $data['room_id'])
            ->where('listing_statuses.listing_id', $data['listing_id'])
            ->where('listing_statuses.status', 'finished')
            ->select('listing_statuses.*')
            ->get();

        $no_finished_listings = count($status_sql2);

        $status_sql3 = DB::table('listing_statuses')
            ->join('listings', 'listings.id', '=', 'listing_statuses.listing_id')
            ->join('users', 'users.id', '=', 'listing_statuses.user_id')
            ->where('listing_statuses.room_id', $data['room_id'])
            ->where('listing_statuses.listing_id', $data['listing_id'])
            ->where('listing_statuses.status', 'ongoing')
            ->select('listing_statuses.*')
            ->get();

        $no_ongoing_listings = count($status_sql3);

        $user_listing_status = DB::table('listing_statuses')
            ->join('listings', 'listings.id', '=', 'listing_statuses.listing_id')
            ->join('users', 'users.id', '=', 'listing_statuses.user_id')
            ->where('listing_statuses.room_id', $data['room_id'])
            ->where('listing_statuses.listing_id', $data['listing_id'])
            ->where('listing_statuses.user_id', $id)
            ->select(
                'listing_statuses.status',
                'listing_statuses.listing_id',
            )
            ->get();
        $status = $user_listing_status->pluck('status')->toArray();
        $result = collect();
        $result = $result->merge(['users_count' => $users_count, 'no_finished_users' => $no_finished_listings, 'no_ongoing_users' => $no_ongoing_listings, 'users_status' => $status]);
        return \response([
            'data' => $result
        ]);
    }

    public
    function deleteScannedProduct(Request $request, int $temp_inventory_id)
    {
        AuthCheck::checkIfUser();
        $id = Auth::id();
        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'listing_id' => 'required|exists:listings,id',
        ]);

        //Sql gets all the rooms , teams and companies of the logged-in user
        $sql = DB::table('listings')
            ->join('room_team_listings', 'room_team_listings.listing_id', '=', 'listings.id')
            ->join('teams', 'teams.id', '=', 'room_team_listings.team_id')
            ->join('rooms', 'rooms.id', '=', 'room_team_listings.room_id')
            ->join('rooms as roo', 'roo.id', '=', 'room_team_listings.room_id')
            ->join('companies', 'companies.id', '=', 'rooms.company_id')
            ->join('user_companies', 'user_companies.company_id', '=', 'companies.id')
            ->join('team_users', 'team_users.team_id', '=', 'teams.id')
            ->join('users', 'users.id', '=', 'team_users.user_id')
            ->where('users.id', '=', $id)
            ->select('rooms.id as room_id',
                'rooms.id as room_id',
                'listings.id as listing_id',
            )
            ->get();
        $roomIds = $sql->pluck('room_id')->toArray();
        $listingIds = $sql->pluck('listing_id')->toArray();

        if (!in_array($data['room_id'], $roomIds)) {
            return response([
                'message' => "Invalid room id.User doesn't have access to specified room.",
            ], 500);
        }

        if (!in_array($data['listing_id'], $listingIds)) {
            return response([
                'message' => "Invalid listing id.User doesn't have access to specified listing.",
            ], 500);
        }

        $temp_inventory_result = Temp_inventory::destroy($temp_inventory_id);

        if (!$temp_inventory_result) {
            return response(['message' => "Failed to remove product"], 500);
        }
        return response(['message' => "Product removed"], 200);
    }

    public
    function updateScannedProduct(Request $request, int $temp_inventory_id)
    {
        AuthCheck::checkIfUser();
        $id = Auth::id();
        $data = $request->validate([
            'qn' => 'required|numeric',
            'room_id' => 'required|exists:rooms,id',
            'listing_id' => 'required|exists:listings,id',
        ]);

        //Sql gets all the rooms , teams and companies of the logged-in user
        $sql = DB::table('listings')
            ->join('room_team_listings', 'room_team_listings.listing_id', '=', 'listings.id')
            ->join('teams', 'teams.id', '=', 'room_team_listings.team_id')
            ->join('rooms', 'rooms.id', '=', 'room_team_listings.room_id')
            ->join('rooms as roo', 'roo.id', '=', 'room_team_listings.room_id')
            ->join('companies', 'companies.id', '=', 'rooms.company_id')
            ->join('user_companies', 'user_companies.company_id', '=', 'companies.id')
            ->join('team_users', 'team_users.team_id', '=', 'teams.id')
            ->join('users', 'users.id', '=', 'team_users.user_id')
            ->where('users.id', '=', $id)
            ->select('rooms.id as room_id',
                'rooms.id as room_id',
                'listings.id as listing_id',
            )
            ->get();
        $roomIds = $sql->pluck('room_id')->toArray();
        $listingIds = $sql->pluck('listing_id')->toArray();

        if (!in_array($data['room_id'], $roomIds)) {
            return response([
                'message' => "Invalid room id.User doesn't have access to specified room.",
            ], 500);
        }

        if (!in_array($data['listing_id'], $listingIds)) {
            return response([
                'message' => "Invalid listing id.User doesn't have access to specified listing.",
            ], 500);
        }

        $temp_inventory = Temp_inventory::find($temp_inventory_id);
        $result = $temp_inventory->update([
            'quantity' => $data['qn']
        ]);

        if (!$result) {
            return response(['message' => "Failed to update scanned data"], 500);
        }
        return response(['message' => "Data updated successfully."], 200);
    }

    /**
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public
    function scanTempInventories(Request $request): Response|Application|ResponseFactory
    {
        AuthCheck::checkIfUser();
        $id = Auth::id();
        $data = $request->validate([
            'data.*.product_id' => 'required|exists:products,id',
            'data.*.qn' => 'required|numeric',
            'room_id' => 'required|exists:rooms,id',
            'listing_id' => 'required|exists:listings,id',
        ]);

        //Sql gets all the rooms , teams and companies of the logged-in user
        $sql = DB::table('listings')
            ->join('room_team_listings', 'room_team_listings.listing_id', '=', 'listings.id')
            ->join('teams', 'teams.id', '=', 'room_team_listings.team_id')
            ->join('rooms', 'rooms.id', '=', 'room_team_listings.room_id')
            ->join('rooms as roo', 'roo.id', '=', 'room_team_listings.room_id')
            ->join('companies', 'companies.id', '=', 'rooms.company_id')
            ->join('user_companies', 'user_companies.company_id', '=', 'companies.id')
            ->join('team_users', 'team_users.team_id', '=', 'teams.id')
            ->join('users', 'users.id', '=', 'team_users.user_id')
            ->where('users.id', '=', $id)
            ->select('rooms.id as room_id',
                'rooms.id as room_id',
                'listings.id as listing_id',
            )
            ->get();
        $roomIds = $sql->pluck('room_id')->toArray();
        $listingIds = $sql->pluck('listing_id')->toArray();

        if (!in_array($data['room_id'], $roomIds)) {
            return response([
                'message' => "Invalid room id.User doesn't have access to specified room.",
            ], 500);
        }

        if (!in_array($data['listing_id'], $listingIds)) {
            return response([
                'message' => "Invalid listing id.User doesn't have access to specified listing.",
            ], 500);
        }

        $sql2 = DB::table('temp_inventories')
            ->where('temp_inventories.user_id', '=', $id)
            ->where('temp_inventories.listing_id', '=', $data['listing_id'])
            ->select(
                'temp_inventories.product_id',
                'temp_inventories.user_id',
                'temp_inventories.quantity',
                'temp_inventories.listing_id',
            )
            ->get();

        $productIds = $sql2->pluck('product_id')->toArray();
        $listingIds = $sql2->pluck('listing_id')->toArray();
        $temp_inventory_add = null;

        if (count($listingIds) > 0) {
            foreach ($data['data'] as $item) {
                $qn = $item['qn'];
                if (in_array($item['product_id'], $productIds)) {
                    $temp_inventory_add = Temp_inventory::where([
                        'user_id' => $id,
                        'listing_id' => $data['listing_id'],
                        'product_id' => $item['product_id'],
                        'room_id' => $data['room_id']
                    ])->get();

                    foreach ($temp_inventory_add as $collection_item) {
                        $collection_item->increment('quantity', $qn);
                        $collection_item->save();
                    }
                }
            }
            if (!in_array($item['product_id'], $productIds)) {
                foreach ($data['data'] as $item) {
                    $productId = $item['product_id'];
                    $qn = $item['qn'];

                    $temp_inventory_add = Temp_inventory::create([
                        'room_id' => $data['room_id'],
                        'listing_id' => $data['listing_id'],
                        'product_id' => $productId,
                        'quantity' => $qn,
                        'user_id' => $id
                    ]);
                }
            }
            return response([
                'message' => "Data added to database.",
                'data' => $temp_inventory_add
            ], 200);
        }

        foreach ($data['data'] as $item) {
            $productId = $item['product_id'];
            $qn = $item['qn'];

            $temp_inventory_add = Temp_inventory::create([
                'room_id' => $data['room_id'],
                'listing_id' => $data['listing_id'],
                'product_id' => $productId,
                'quantity' => $qn,
                'user_id' => $id
            ]);
        }

        if (empty($temp_inventory_add)) {
            return response([
                'message' => "Failed to add data to database",
            ], 500);
        }

        return response([
            'message' => "Data added to database",
        ], 200);
    }

    /**
     * @param Request $request
     * @return Response|Collection|Application|ResponseFactory
     */
    public
    function getScannedProducts(Request $request): Response|\Illuminate\Support\Collection|Application|ResponseFactory
    {
        AuthCheck::checkIfUser();
        $id = Auth::id();
        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'listing_id' => 'required|exists:listings,id',
        ]);

        //Sql gets all the rooms , teams and companies of the logged-in user
        $sql = DB::table('listings')
            ->join('room_team_listings', 'room_team_listings.listing_id', '=', 'listings.id')
            ->join('teams', 'teams.id', '=', 'room_team_listings.team_id')
            ->join('rooms', 'rooms.id', '=', 'room_team_listings.room_id')
            ->join('rooms as roo', 'roo.id', '=', 'room_team_listings.room_id')
            ->join('companies', 'companies.id', '=', 'rooms.company_id')
            ->join('user_companies', 'user_companies.company_id', '=', 'companies.id')
            ->join('team_users', 'team_users.team_id', '=', 'teams.id')
            ->join('users', 'users.id', '=', 'team_users.user_id')
            ->where('users.id', '=', $id)
            ->select('rooms.id as room_id',
                'rooms.id as room_id',
                'listings.id as listing_id',
            )
            ->get();
        $roomIds = $sql->pluck('room_id')->toArray();
        $listingIds = $sql->pluck('listing_id')->toArray();

        if (!in_array($data['room_id'], $roomIds)) {
            return response([
                'message' => "Invalid room id.User doesn't have access to specified room.",
            ], 500);
        }

        if (!in_array($data['listing_id'], $listingIds)) {
            return response([
                'message' => "Invalid listing id.User doesn't have access to specified listing.",
            ], 500);
        }
        return DB::table('temp_inventories')
            ->join('users', 'users.id', '=', 'temp_inventories.user_id')
            ->join('products', 'products.id', '=', 'temp_inventories.product_id')
            ->where('temp_inventories.listing_id', $data['listing_id'])
            ->where('temp_inventories.room_id', $data['room_id'])
            ->where('temp_inventories.user_id', $id)
            ->select(
                'temp_inventories.id',
                'users.first_name',
                'users.last_name',
                'temp_inventories.quantity',
                'products.name',
            )
            ->get();
    }

    public
    function getListings()
    {
        AuthCheck::checkIfUser();

        $id = Auth::id();
        $sql = DB::table('listings')
            ->join('room_team_listings', 'room_team_listings.listing_id', '=', 'listings.id')
            ->join('teams', 'teams.id', '=', 'room_team_listings.team_id')
            ->join('rooms', 'rooms.id', '=', 'room_team_listings.room_id')
            ->join('companies', 'companies.id', '=', 'rooms.company_id')
            ->leftJoin('user_companies', 'user_companies.company_id', '=', 'companies.id')
            ->join('team_users', 'team_users.team_id', '=', 'teams.id')
            ->join('users', 'users.id', '=', 'team_users.user_id')
            ->where('team_users.user_id', '=', $id)
            ->where('listings.status', '=', 'ongoing')
            ->select('rooms.id as room_id',
                'users.id as user_id',
                'teams.id as team_id',
                'rooms.name as room_name',
                'rooms.description as room_description',
                'rooms.location as room_location',
                'listings.status as listing_status',
                'listings.listing_name',
                'companies.co_name',
                'listings.start_time',
                'listings.id as listing_id',
            )
            ->get();
        $listing_ids = $sql->pluck('listing_id')->toArray();
        $users_listings_status_sql = [];

        if ($sql->isEmpty()) {
            return response([
                'data' => false
            ], 200);
        }

        for ($i = 0; $i <= count($listing_ids) - 1; $i++) {
            $users_listings_status_sql[$i] = DB::table('listing_statuses')
                ->join('listings', 'listings.id', '=', 'listing_statuses.listing_id')
                ->join('users', 'users.id', '=', 'listing_statuses.user_id')
                ->where('listing_statuses.listing_id', '=', $listing_ids[$i])
                ->select(
                    'listing_statuses.status',
                    'listing_statuses.listing_id',
                    'listing_statuses.user_id',
                )
                ->get()->toArray();
        }

        if (empty($users_listings_status_sql)) {
            return response([
                'message' => 'No listings created.'
            ], 500);
        }
        //Counts is the total number of ids in each listing
        $counts = [];
        for ($i = 0; $i < count($users_listings_status_sql) - 1; $i++) {
            $counts = [];
            foreach ($users_listings_status_sql as $key => $array) {
                $count = 0;
                foreach ($array as $item) {
                    $count++;
                }
                $counts[$key] = $count;
            }
        }
        //Rewrite the $sql collection, so it contains the number of users in that listing
        $sql = $sql->map(function ($item, $key) use ($counts, $users_listings_status_sql) {
            //$item is one value in the $sql collection, in this line I am adding a new value to the collection
            $item->total_no_users = $counts[$key];
            $finished_count = 0;
            //$users_listings_status_sql[$key] is the array which holds the data from the listing_statuses array and the status value.
            foreach ($users_listings_status_sql[$key] as $inner_item) {
                //$inner_item is the stdClass object value
                if ($inner_item->status == 'finished') {
                    //If the stdClass status value for the particular user is = finished increase the amount of finished users
                    $finished_count++;
                }
            }
            //Return the no of finished users into the main $sql/$result collection
            $item->total_no_finished_user_ids = $finished_count;
            return $item;
        });

        //This counts the no of users which are assigned to a listing
        $sql = $sql->map(function ($item, $key) use ($counts) {
            $item->total_no_users = $counts[$key];
            return $item;
        });

        return response([
            'data' => $sql
        ], 200);
    }

    /**
     * @param Request $request
     * @param $id
     * @return Application|ResponseFactory|Response
     */
    public
    function updateListing(Request $request, $id): Response|Application|ResponseFactory
    {
        AuthCheck::checkIfBoss();

        $fields = $request->validate([
            'status' => ['required', Rule::in(['idle', 'ongoing', 'finished', 'failed'])]
        ]);

        $user = Listing::find($id);

        $user->update(['status' => $fields['status']]);
        $user->refresh();
        $response = [
            'message' => "Listing status has been changed to " . $fields['status'],
        ];
        return response($response, 201);
    }

    /**
     * Display a listing of the resource.
     *
     * @param int $company_id
     * @return JsonResponse|Response
     */
    public
    function index(): Response|JsonResponse
    {
        AuthCheck::checkIfBoss();
        $id = Auth::id();
        //Sql gets all the rooms , teams and companies of the logged-in user
//        $sql = DB::table('listings')
//            ->join('room_team_listings', 'room_team_listings.listing_id', '=', 'listings.id')
//            ->join('teams', 'teams.id', '=', 'room_team_listings.team_id')
//            ->join('rooms', 'rooms.id', '=', 'room_team_listings.room_id')
//            ->join('companies', 'companies.id', '=', 'rooms.company_id')
//            ->join('companies as comp', 'comp.id', '=', 'listings.company_id')
//            ->join('user_companies', 'user_companies.user_id', '=', 'companies.id')
//            ->join('users', 'users.id', '=', 'user_companies.user_id')
//            ->where('users.id', 3)
//            ->where('users.role', 'boss')
//            ->select('listings.id'
//                , 'listings.listing_name'
//                , 'listings.description'
//                , 'listings.status'
//                , 'companies.co_name'
//                , 'users.id as users_id'
//            )
//            ->get();
        $sql = DB::table('companies')
            ->join('listings', 'listings.company_id', '=', 'companies.id')
            ->join('user_companies', 'user_companies.company_id', '=', 'listings.company_id')
            ->join('users', 'users.id', '=', 'user_companies.user_id')
            ->where('user_companies.user_id', '=', $id)
            ->where('users.role', 'boss')
            ->select('listings.id'
                , 'listings.listing_name'
                , 'listings.description'
                , 'listings.status'
                , 'companies.co_name'
                , 'companies.id as company_id'
            )
            ->get();


        if (empty($sql)) {
            return response([
                'message' => "User doesn't have any listings",
            ], 500);
        }

        return \response()->json($sql);
    }

    /**
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public
    function changeListingStatus(Request $request)
    {
        $id = Auth::id();
        AuthCheck::checkIfUser();

        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'listing_id' => 'required|exists:listings,id',
        ]);

        //Sql gets all the rooms , teams and companies of the logged-in user
        $sql = DB::table('listings')
            ->join('room_team_listings', 'room_team_listings.listing_id', '=', 'listings.id')
            ->join('rooms', 'rooms.id', '=', 'room_team_listings.room_id')
            ->join('teams', 'teams.id', '=', 'room_team_listings.team_id')
            ->join('listing_statuses', 'listing_statuses.listing_id', '=', 'listings.id')
            ->join('users', 'users.id', '=', 'listing_statuses.user_id')
            ->where('users.id', '=', $id)
            ->where('rooms.id', '=', $data['room_id'])
            ->where('listings.id', '=', $data['listing_id'])
            ->select(
                'listings.id as listing_id'
                , 'listings.listing_name'
                , 'listing_statuses.status'
                , 'rooms.id as room_id'
                , 'rooms.name as room_name'
            )
            ->get();

        $roomIds = $sql->pluck('room_id')->toArray();
        $listingIds = $sql->pluck('listing_id')->toArray();

        if (!in_array($data['room_id'], $roomIds)) {
            return response([
                'message' => "Invalid room id. User doesn't have access to specified room.",
            ], 500);
        }

        if (!in_array($data['listing_id'], $listingIds)) {
            return response([
                'message' => "Invalid listing id. User doesn't have access to specified listing",
            ], 500);
        }

        $status = $sql->pluck('status')->implode(',');
        $new_status = ($status == "ongoing") ? "finished" : "ongoing";

        $listing_statuses = ListingStatus::where('listing_id', $data['listing_id'])
            ->where('user_id', $id)
            ->where('status', $status)
            ->update(['status' => $new_status]);


        if ($listing_statuses == 0) {
            return response([
                'message' => "Failed changing listing status",
            ], 500);
        }

        return response([
            'message' => "Listing status changed successfully",
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     */
    public
    function store(Request $request)
    {
        AuthCheck::checkIfBoss();
        $id = Auth::id();
        $data = $request->validate([
//            'team_id' => 'required|exists:teams,id',
//            'room_id' => 'required|exists:rooms,id',
            'company_id' => 'required|exists:companies,id',
            'start_time' => 'required',
            'listing_name' => 'required',
            'description' => 'required',
            'status' => ['required', Rule::in(['idle', 'ongoing', 'finished', 'failed'])],
        ]);

        //Sql gets all the rooms , teams and companies of the logged-in user
//        $sql = DB::table('users')
//            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
//            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
//            ->join('rooms', 'rooms.company_id', '=', 'companies.id')
//            ->join('room_teams', 'room_teams.room_id', '=', 'rooms.id')
//            ->join('teams', 'room_teams.team_id', '=', 'teams.id')
//            ->join('companies as comp', 'comp.id', '=', 'rooms.company_id')
//            ->join('users as us', 'us.id', '=', 'user_companies.user_id')
//            ->where('user_companies.user_id', '=', $id)
//            ->select(
//                'companies.id as co_id'
//                , 'companies.co_name'
//                , 'rooms.id as room_id'
//                , 'rooms.name as room_name'
//                , 'teams.id as team_id'
//            )
//            ->get();

//        $roomIds = $sql->pluck('room_id')->toArray();
//        $teamsId = $sql->pluck('team_id')->toArray();

//        if (!in_array($data['room_id'], $roomIds)) {
//            return response([
//                'message' => "Invalid room id.User doesn't have access to specified room.",
//            ], 500);
//        }

//        if (!in_array($data['team_id'], $teamsId)) {
//            return response([
//                'message' => "Invalid team id.User doesn't have access to specified team",
//            ], 500);
//        }

//        $startTime = $data['start_time'] == 1 ? now() : $data['start_time'];

        $listing = Listing::create([
            'start_time' => $data['start_time'],
            'listing_name' => $data['listing_name'],
            'description' => $data['description'],
            'status' => $data['status'],
            'company_id' => $data['company_id'],
            'created_at' => now()
        ]);

//        $room_team_listing = Room_team_listings::create([
//            'team_id' => $data['team_id'],
//            'room_id' => $data['room_id'],
//            'listing_id' => $listing->id,
//        ]);

        if (empty($listing)) {
            return response([
                'message' => "Failed to start a new listing.",
            ], 500);
        }

//        $listing_statuses = ListingStatus::create([
//            'listing_id' => $listing->id,
//            'user_id' => $id,
//            'status' => 'ongoing'
//        ]);
//
//        if (empty($listing_statuses)) {
//            return response([
//                'message' => "Failed to start a new listing.2",
//            ], 500);
//        }

        return response([
            'message' => "Listing " . $data['listing_name'] . " started at: " . $data['start_time'],
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePopisRequest $request
     * @param Listing $popis
     * @return Response
     */
    public
    function update(Request $request, int $listing_id)
    {
        AuthCheck::checkIfBoss();
        $id = Auth::id();
        $data = $request->validate([
//            'team_id' => 'required|exists:teams,id',
//            'room_id' => 'required|exists:rooms,id',
//            'start_time' => 'required',
            'listing_name' => 'required',
            'description' => 'required',
            'status' => ['required', Rule::in(['idle', 'ongoing', 'finished', 'failed'])],
        ]);

        //Sql gets all the rooms , teams and companies of the logged-in user
//        $sql = DB::table('users')
//            ->join('user_companies', 'user_companies.user_id', '=', 'users.id')
//            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
//            ->join('rooms', 'rooms.company_id', '=', 'companies.id')
//            ->join('room_teams', 'room_teams.room_id', '=', 'rooms.id')
//            ->join('teams', 'room_teams.team_id', '=', 'teams.id')
//            ->join('companies as comp', 'comp.id', '=', 'rooms.company_id')
//            ->join('users as us', 'us.id', '=', 'user_companies.user_id')
//            ->where('user_companies.user_id', '=', $id)
//            ->select(
//                'companies.id as co_id'
//                , 'companies.co_name'
//                , 'rooms.id as room_id'
//                , 'rooms.name as room_name'
//                , 'teams.id as team_id'
//            )
//            ->get();
//
//        $roomIds = $sql->pluck('room_id')->toArray();
//        $teamsId = $sql->pluck('team_id')->toArray();
//
//        if (!in_array($data['room_id'], $roomIds)) {
//            return response([
//                'message' => "Invalid room id.User doesn't have access to specified room.",
//            ], 500);
//        }
//
//        if (!in_array($data['team_id'], $teamsId)) {
//            return response([
//                'message' => "Invalid team id.User doesn't have access to specified team",
//            ], 500);
//        }
        $listing = Listing::find($listing_id);
        $listing->update([
            'listing_name' => $data['listing_name'],
            'description' => $data['description'],
            'status' => $data['status'],
            'updated_at' => now()
        ]);


//        $room_team_listing = Room_team_listings::where('listing_id', $listing_id)->update([
//            'team_id' => $data['team_id'],
//            'room_id' => $data['room_id'],
//            'listing_id' => $listing->id,
//        ]);

        if (empty($listing)) {
            return response([
                'message' => "Failed to update listing.",
            ], 500);
        }

        return response([
            'message' => "Listing " . $data['listing_name'] . " updated"
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Listing $popis
     * @return Response
     */
    public
    function destroy(Request $request)
    {
        AuthCheck::checkIfBoss();

        $data = $request->validate([
            'listing_id' => 'required|exists:listings,id'
        ]);

        $listing = Listing::destroy($data['listing_id']);

        $room_team_listing = Room_team_listings::where('listing_id', $data['listing_id'])->delete();

        if ($room_team_listing == 0 and $listing == 0) {
            return response([
                'message' => "Failed to delete listing.",
            ], 500);
        }

        return response([
            'message' => "Listing deleted."
        ], 200);
    }
}
