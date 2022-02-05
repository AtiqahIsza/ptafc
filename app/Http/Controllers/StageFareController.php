<?php

namespace App\Http\Controllers;

use App\Models\Stage;
use App\Models\StageFare;
use Illuminate\Http\Request;

class StageFareController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        return view('settings.manageStageFare');
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
     * @param  \App\Models\StageFare  $stageFare
     * @return \Illuminate\Http\Response
     */
    public function show(StageFare $stageFare)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\StageFare  $stageFare
     * @return \Illuminate\Http\Response
     */
    public function edit(StageFare $stageFare)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StageFare  $stageFare
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        if ($request->ajax()) {
            StageFare::find($request->pk)
                ->update([
                    $request->name => $request->value
                ]);

            return response()->json(['success' => true]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StageFare  $stageFare
     * @return \Illuminate\Http\Response
     */
    public function destroy(StageFare $stageFare)
    {
        //
    }
}
