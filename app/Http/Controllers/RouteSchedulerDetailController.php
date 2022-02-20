<?php

namespace App\Http\Controllers;

use App\Models\RouteSchedulerDetail;
use Illuminate\Http\Request;

class RouteSchedulerDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        return view('settings.manageScheduler');
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
     * @param  \App\Models\RouteSchedulerDetail  $routeSchedulerDetail
     * @return \Illuminate\Http\Response
     */
    public function show(RouteSchedulerDetail $routeSchedulerDetail)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RouteSchedulerDetail  $routeSchedulerDetail
     * @return \Illuminate\Http\Response
     */
    public function edit(RouteSchedulerDetail $routeSchedulerDetail)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RouteSchedulerDetail  $routeSchedulerDetail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RouteSchedulerDetail $routeSchedulerDetail)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RouteSchedulerDetail  $routeSchedulerDetail
     * @return \Illuminate\Http\Response
     */
    public function destroy(RouteSchedulerDetail $routeSchedulerDetail)
    {
        //
    }
}
