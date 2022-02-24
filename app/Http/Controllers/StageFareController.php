<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use App\Models\Stage;
use App\Models\StageFare;
use Illuminate\Http\Request;
use Symfony\Component\Console\Output\ConsoleOutput;

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
        $validatedData = Validator::make($request->all(), [
            'fare' => ['required', 'array'],
            'fare.*' => ['required', 'string', 'max:255'],
            'fromStage' => ['required', 'array'],
            'fromStage.*' => ['required', 'string', 'max:255'],
            'toStage'=> ['required', 'array'],
            'toStage.*' => ['required', 'string', 'max:255'],
            'routeId'=> ['required', 'string', 'max:255'],
            'fareType'=> ['required', 'string', 'max:255'],
        ])->validate();

        foreach ($validatedData['fare'] as $i => $validatedData['fare']) {
            $existedFare = StageFare::where([
                ['route_id', $validatedData['routeId']],
                ['fromstage_stage_id', $validatedData['fromStage'][$i]],
                ['tostage_stage_id', $validatedData['toStage'][$i]]
            ])->first();

            if($existedFare){

                if($validatedData['fareType'] == 'Adult'){
                    $updateFare = StageFare::where('route_id', $validatedData['routeId'])
                    ->where('tostage_stage_id', $validatedData['toStage'][$i])
                    ->where('fromstage_stage_id', $validatedData['fromStage'][$i])
                    ->update(['fare' => $validatedData['fare']]);
                }
                else{
                    $updateFare = StageFare::where('route_id', $validatedData['routeId'])
                    ->where('tostage_stage_id', $validatedData['toStage'][$i])
                    ->where('fromstage_stage_id', $validatedData['fromStage'][$i])
                    ->update(['consession_fare' => $validatedData['fare']]);
                }

                // if($updateFare){
                //     return redirect()->to('/settings/managestagefare')->with(['message' => 'Stage fare updated successfully!']);
                // }
            }
            else{
                if($validatedData['fareType'] == 'Adult'){
                    $createFare = StageFare::create([
                        'fare' =>  $validatedData['fare'],
                        'route_id' => $validatedData['routeId'],
                        'fromstage_stage_id' => $validatedData['fromStage'][$i],
                        'tostage_stage_id' => $validatedData['toStage'][$i],
                    ]);
                }
                else{
                    $createFare = StageFare::create([
                        'consession_fare' =>  $validatedData['fare'],
                        'route_id' => $validatedData['routeId'],
                        'fromstage_stage_id' => $validatedData['fromStage'][$i],
                        'tostage_stage_id' => $validatedData['toStage'][$i],
                    ]);
                }

                // if($createFare){
                //     return redirect()->to('/settings/managestagefare')->with(['message' => 'Stage fare created successfully!']);
                // }
            }
        }
        return redirect()->to('/settings/manageStageFare')->with(['message' => 'Stage fare created successfully!']);
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
