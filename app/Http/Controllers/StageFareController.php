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
    public function create(Request $request)
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
        if($request->ajax())
    	{
    		if($request->action == 'edit')
    		{
    			$data = array(
    				'fare' => $request->fare,
    				'to_stage' => $request->to_stage,
    				'from_stage' =>	$request->from_stage
    			);
    			DB::table('sample_datas')
    				->where('id', $request->id)
    				->update($data);
    		}
    		if($request->action == 'delete')
    		{
    			DB::table('sample_datas')
    				->where('id', $request->id)
    				->delete();
    		}
    		return response()->json($request);
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
