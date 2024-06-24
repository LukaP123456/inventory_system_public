<?php

namespace App\Http\Controllers;

use App\Models\ListingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListingStatusRequest;
use App\Http\Requests\UpdateListingStatusRequest;

class ListingStatusController extends Controller
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
     * @param  \App\Http\Requests\StoreListingStatusRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreListingStatusRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ListingStatus  $listingStatus
     * @return \Illuminate\Http\Response
     */
    public function show(ListingStatus $listingStatus)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ListingStatus  $listingStatus
     * @return \Illuminate\Http\Response
     */
    public function edit(ListingStatus $listingStatus)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateListingStatusRequest  $request
     * @param  \App\Models\ListingStatus  $listingStatus
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateListingStatusRequest $request, ListingStatus $listingStatus)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ListingStatus  $listingStatus
     * @return \Illuminate\Http\Response
     */
    public function destroy(ListingStatus $listingStatus)
    {
        //
    }
}
