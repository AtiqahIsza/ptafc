<?php

namespace App\Http\Controllers;

use App\Models\Stage;
use App\Models\StageMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Console\Output\ConsoleOutput;

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
    public function store(Request $request): JsonResponse
    {
        $out = new ConsoleOutput();
        $stageMaps = $request->markers;

        try{
            foreach($stageMaps as $key => $value){
                /*$out->writeln($value['lat']);
                $out->writeln(round($value['lat'],10));
                $out->writeln($value['long']);
                $out->writeln(round($value['long'],10));
                $out->writeln($value['sequence']);
                $out->writeln($value['route_id']);*/

                $newMap = new StageMap();
                $newMap->longitude = round($value['long'],10);
                $newMap->latitude = round($value['lat'],10);
                $newMap->sequence = $value['sequence'];
                $newMap->stage_id = $value['stage_id'];
                $newMap->save();
            }
            return $this->returnResponse(1, "Stage Map Successfully Stored", "Stage Map Successfully Stored");
        }
        catch(\Exception $e){
            $out->writeln($e);
            $error['error'] =  $e;
            return $this->returnResponse(2, $error, "Error Occurred, see error log");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\StageMap  $stageMap
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $stage = Stage::where('id', $request->route('id'))->first();
        $stageMaps = StageMap::select('latitude', 'longitude')
            ->where('stage_id', $request->route('id'))
            ->orderby('sequence')
            ->get();
        return view('settings.viewStageMap', compact('stage','stageMaps'));
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
