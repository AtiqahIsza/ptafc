<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\RouteMap;
use Illuminate\Http\Request;

class RouteMapController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $route = Route::where('id', $request->route('id'))->first();

        return view('settings.addRouteMap', compact('route'));
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RouteMap  $routeMap
     * @return \Illuminate\Http\Response
     */
    public function show(RouteMap $routeMap)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RouteMap  $routeMap
     * @return \Illuminate\Http\Response
     */
    public function edit(RouteMap $routeMap)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RouteMap  $routeMap
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RouteMap $routeMap)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RouteMap  $routeMap
     * @return \Illuminate\Http\Response
     */
    public function destroy(RouteMap $routeMap)
    {
        //
    }
}
