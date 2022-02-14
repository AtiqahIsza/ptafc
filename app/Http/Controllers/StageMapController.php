<?php

namespace App\Http\Controllers;

use App\Models\Stage;
use App\Models\StageMap;
use Illuminate\Http\Request;

class StageMapController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $stage = Stage::where('id', $request->route('id'))->first();

        return view('settings.addStageMap', compact('stage'));
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
     * @param  \App\Models\StageMap  $stageMap
     * @return \Illuminate\Http\Response
     */
    public function show(StageMap $stageMap)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\StageMap  $stageMap
     * @return \Illuminate\Http\Response
     */
    public function edit(StageMap $stageMap)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StageMap  $stageMap
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StageMap $stageMap)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StageMap  $stageMap
     * @return \Illuminate\Http\Response
     */
    public function destroy(StageMap $stageMap)
    {
        //
    }
}
