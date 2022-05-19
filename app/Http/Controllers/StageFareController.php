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
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\StageFare $stageFare
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateOld(Request $request)
    {
        //dd($request->all());
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  updateStageFare()");
        $validatedData = Validator::make($request->all(), [
            'fare' => ['required', 'array'],
            'fare.*' => ['string'],
            'fromStage' => ['required', 'array'],
            'fromStage.*' => ['required', 'string'],
            'toStage'=> ['required', 'array'],
            'toStage.*' => ['required', 'string'],
            'routeId'=> ['required', 'int'],
            'fareType'=> ['required', 'string'],
        ])->validate();
        $out->writeln("YOU ARE after validatedData");

        //dd($validatedData['fare']);
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

    public function update(Request $request)
    {
        //dd($request->all());
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  updateStageFare()");
        $validatedData = Validator::make($request->all(), [
            'fare' => ['required', 'array'],
            'fare.*' => ['int'],
            'fromStage' => ['required', 'array'],
            'fromStage.*' => ['required', 'string'],
            'toStage' => ['required', 'array'],
            'toStage.*' => ['required', 'string'],
            'routeId' => ['required', 'int'],
            'fareType' => ['required', 'string'],
        ]);
        //dd( $request->fare);
        $out->writeln("YOU ARE after validatedData ");

        //dd($request->all());
        //dd($validatedData['fare']);
        $countUpdate = 0;
        $countCreate = 0;
        foreach ($request->fare as $i => $fareArr) {
            $existedFare = StageFare::where([
                ['route_id', $request->routeId],
                ['fromstage_stage_id', $request->fromStage[$i]],
                ['tostage_stage_id', $request->toStage[$i]]
            ])->first();

            $out->writeln("request->fromStage[i] " . $request->fromStage[$i]);
            $out->writeln("request->toStage[i] " . $request->toStage[$i]);

            if ($existedFare) {
                $out->writeln("YOU ARE in existedFare" . $existedFare);

                if ($request->fareType == 'Adult') {
                    $out->writeln("YOU ARE in faretype" . $fareArr);
                    $updateFare = StageFare::where('route_id', $request->routeId)
                        ->where('tostage_stage_id', $request->toStage[$i])
                        ->where('fromstage_stage_id', $request->fromStage[$i])
                        ->update(['fare' => $fareArr]);
                    //dd($updateFare);
                    if ($updateFare) {
                        $countUpdate++;
                    }
                }
            } else {
                if ($request->fareType == 'Adult') {
                    $createFare = StageFare::create([
                        'fare' => $fareArr,
                        'route_id' => $request->routeId,
                        'fromstage_stage_id' => $request->fromStage[$i],
                        'tostage_stage_id' => $request->toStage[$i],
                    ]);
                    //dd($createFare);
                    if ($createFare) {
                        $countCreate++;
                    }
                }
            }
        }
        if ($countUpdate == 0 && $countCreate == 0){
            return redirect()->to('/settings/manageStageFare')->with(['message' => 'Stage fare failed to update!']);
        }
        return redirect()->to('/settings/manageStageFare')->with(['message' => 'Stage fare updated successfully!']);

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
