<?php

namespace App\Http\Controllers;

use App\Models\AuthCheck;
use App\Models\Inventory;
use App\Http\Requests\StoreInventarRequest;
use App\Http\Requests\UpdateInventarRequest;
use App\Models\Product;
use App\Models\Room;
use App\Models\Temp_inventory;
use App\Models\User;
use App\Models\User_company;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function addToInventory(Request $request): mixed
    {
        AuthCheck::checkIfUser();

        return Inventory::create([
            'product_id' => $request->product_id,
            'room_id' => $request->room_id,
            'quantity' => $request->quantity,
            'created_at' => now(),
        ]);
    }

    /**
     * @return bool
     */
    public function checkAuthorization(): bool
    {
        $id = Auth::id();
        $user = User::where('id', $id)->firstOrFail();

        if ($user->role == 'boss') {
            return true;
        }

        if ($user->role == 'admin') {
            return true;
        }
        return false;
    }

    /**
     * Display a listing of the resource.
     *
     */
    public function index(): Collection
    {
        $id = Auth::id();
        AuthCheck::checkIfUser();

        return DB::table('archive_inventories')
            ->join('rooms', 'rooms.id', '=', 'archive_inventories.room_id')
            ->join('companies', 'companies.id', '=', 'rooms.company_id')
            ->join('user_companies', 'user_companies.company_id', '=', 'companies.id')
            ->join('users', 'users.id', '=', 'user_companies.user_id')
            ->join('products', 'products.id', '=', 'archive_inventories.product_id')
            ->select('archive_inventories.id', 'archive_inventories.action', 'rooms.name as room_name', 'rooms.location as room_location', 'products.id as product_id', 'products.name as product_name', 'companies.co_name')
            ->where('users.id', $id)
            ->get();
    }

    /**
     * @return Collection|string
     */
    public function getArchive()
    {
        AuthCheck::checkIfBoss();
        $id = Auth::id();

        $company_ids = User_company::where('user_id', $id)->get()->pluck('company_id');
        $product_ids = collect();
        $room_ids = collect();
        foreach ($company_ids as $company_id) {
            $products []= Product::where('company_id', '=', $company_id)->select('id')->get()->toArray();
        }
        $new_products = Arr::flatten( $products);
        foreach ($company_ids as $company_id) {
            $room_ids[] = Room::where('company_id', '=', $company_id)->get()->pluck('id');
        }
        $result = [];
        for ($i = 0; $i < count($new_products) ; $i++) {
            for ($j = 0; $j < count($room_ids) ; $j++) {
                $result = array_merge($result, DB::table('archive_inventories')
                    ->select('archive_inventories.action', 'rooms.name as room_name', 'rooms.location as room_location', 'products.id as product_id', 'products.name as product_name', 'companies.co_name')
                    ->join('rooms', 'rooms.id', '=', 'archive_inventories.room_id')
                    ->join('companies', 'companies.id', '=', 'rooms.company_id')
                    ->join('products', 'products.id', '=', 'archive_inventories.product_id')
                    ->where('archive_inventories.product_id', '=', $new_products[$i])
                    ->where('archive_inventories.room_id', '=', $room_ids[$j])
                    ->get()->toArray());
            }
        }

        return $result;

    }

    /**
     * @param Request $request
     * @param $id
     */
    public function updateStatus(Request $request, $id): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {

        $inventory = Temp_inventory::find($id);

        if (!$inventory->update($request->all())) {
            $response = [
                'message' => "Failed to update inventory status",
            ];
            return response($response, 500);
        }

        $inventory->refresh();
        $response = [
            'message' => "Inventory status updated successfully",
        ];
        return response($response, 201);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\StoreInventarRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInventarRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Inventory $inventar
     * @return \Illuminate\Http\Response
     */
    public function show(Inventory $inventar)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Inventory $inventar
     * @return \Illuminate\Http\Response
     */
    public function edit(Inventory $inventar)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateInventarRequest $request
     * @param \App\Models\Inventory $inventar
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInventarRequest $request, Inventory $inventar)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Inventory $inventar
     * @return \Illuminate\Http\Response
     */
    public function destroy(Inventory $inventar)
    {
        //
    }
}
