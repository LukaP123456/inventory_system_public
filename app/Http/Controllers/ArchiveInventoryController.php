<?php

namespace App\Http\Controllers;

use App\Models\Archive_inventory;
use App\Http\Requests\StoreArchive_inventoryRequest;
use App\Http\Requests\UpdateArchive_inventoryRequest;
use App\Models\AuthCheck;
use App\Models\Product;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ArchiveInventoryController extends Controller
{
    public function showRoomArchive(int $id)
    {
        AuthCheck::checkIfBoss();

        $sql = DB::table('archive_inventories')
            ->select('archive_inventories.*','rooms.*','products.*','users.*')
            ->join('rooms', 'rooms.id', '=', 'archive_inventories.room_id')
            ->join('products', 'products.id', '=', 'archive_inventories.product_id')
            ->join('users', 'users.id', '=', 'archive_inventories.user_id')
            ->where('archive_inventories.room_id',$id)
            ->get();

        $response = [
            'data' =>$sql,
        ];
        return response($response, 201);


    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(): Response
    {
        AuthCheck::checkIfBoss();

        $sql = DB::table('archive_inventories')
            ->select('archive_inventories.*','rooms.*','products.*','users.*')
            ->join('rooms', 'rooms.id', '=', 'archive_inventories.room_id')
            ->join('products', 'products.id', '=', 'archive_inventories.product_id')
            ->join('users', 'users.id', '=', 'archive_inventories.user_id')
            ->get();

        $response = [
            'data' =>$sql,
        ];
        return response($response, 201);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreArchive_inventoryRequest $request
     * @return Response
     */
    public function store(Request $request): Response
    {
       AuthCheck::checkIfBoss();

        $fields = $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'product_id' => 'required|exists:products,id',
        ]);

        $add_to_room = Archive_inventory::create([
            'user_id' => $fields['user_id'],
            'room_id' => $fields['room_id'],
            'product_id' => $fields['product_id']
        ]);

        if (empty($add_to_room)) {
            $response = [
                'message' => "Failed to add product to room",
            ];
            return response($response, 500);
        }

        $response = [
            'message' => "Added product to room",
        ];
        return response($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param Archive_inventory $archive_inventory
     * @return Response
     */
    public function show(Archive_inventory $archive_inventory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Archive_inventory $archive_inventory
     * @return Response
     */
    public function edit(Archive_inventory $archive_inventory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateArchive_inventoryRequest $request
     */
    public function update(Request $request, int $id)
    {
       AuthCheck::checkIfBoss();

        $product = Archive_inventory::find($id);
        $product->update($request->all());
        return $product;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Application|ResponseFactory|Response|int
     */
    public function destroy(int $id): Response|int|Application|ResponseFactory
    {
       AuthCheck::checkIfBoss();

        return Archive_inventory::destroy($id);
    }
}
