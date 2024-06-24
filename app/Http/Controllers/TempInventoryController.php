<?php

namespace App\Http\Controllers;

use App\Models\Temp_inventory;
use App\Http\Requests\StoreTemp_inventoryRequest;
use App\Http\Requests\UpdateTemp_inventoryRequest;

class TempInventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \App\Http\Requests\StoreTemp_inventoryRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTemp_inventoryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Temp_inventory  $temp_inventory
     * @return \Illuminate\Http\Response
     */
    public function show(Temp_inventory $temp_inventory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Temp_inventory  $temp_inventory
     * @return \Illuminate\Http\Response
     */
    public function edit(Temp_inventory $temp_inventory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTemp_inventoryRequest  $request
     * @param  \App\Models\Temp_inventory  $temp_inventory
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTemp_inventoryRequest $request, Temp_inventory $temp_inventory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Temp_inventory  $temp_inventory
     * @return \Illuminate\Http\Response
     */
    public function destroy(Temp_inventory $temp_inventory)
    {
        //
    }
}
