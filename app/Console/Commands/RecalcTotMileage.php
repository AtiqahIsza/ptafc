<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TripDetail;
use App\Models\VehiclePosition;
use Illuminate\Support\Carbon;

class RecalcTotMileage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcTotMileage:ctm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Total Mileage All Trip for Yesterday';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $yesterdayDate = Carbon::yesterday()->format('Y-m-d');
        $startDay = new Carbon($yesterdayDate);
        $endDay = new Carbon($yesterdayDate . '23:59:59');
        $allTrips = TripDetail::whereBetween('start_trip', [$startDay, $endDay])
                    ->orWhereBetween('upload_date', [$startDay, $endDay])
                    ->orderBy('start_trip')
                    ->get();
                    
        foreach($allTrips as $allTrip){
            $this->info("Iterate each trip...");

            if($allTrip->total_mileage==0){
                $this->info("Trip ID: " . $allTrip->trip_number);

                $allLoc = VehiclePosition::where('trip_id', $allTrip->trip_number)->get();
                
                $count = 0;
                $pastLong = 0;
                $pastLat = 0;
                $mileage = 0;
                if(count($allLoc)>0){
                    foreach($allLoc as $allGPS){
                        if($count==0){
                            $this->info("In count==0");
                            $pastLong = $allGPS->longitude;
                            $pastLat = $allGPS->latitude;
                        }else{
                            $lastLong = $allGPS->longitude;
                            $lastLat = $allGPS->latitude;
                            $this->info("1st Long: " . $pastLong . " 1st Lat: " . $pastLat . " 2nd Long: " . $lastLong . " 2nd Lat: " . $lastLat);
                            
                            $theta = $pastLong - $lastLong;

                            $calc = 6371 * acos( cos( deg2rad($pastLat) ) 
                            * cos( deg2rad( $lastLat ) ) 
                            * cos( deg2rad( $theta) ) + sin( deg2rad($pastLat) ) 
                            * sin( deg2rad( $lastLat ) ) );

                            $this->info("Calculated distance: " . $calc);
                            if(!is_nan($calc)){
                                $this->info("In !is_nan()");
                                $mileage += $calc;
                            }
                            $pastLong = $allGPS->longitude;
                            $pastLat = $allGPS->latitude;
                            $this->info("Accumulated mileage: " . $mileage);
                        }
                        $count++;
                    }
                    $mileageFormat = number_format((float)$mileage, 2, '.', '');
                    $this->info("FORMATTED MILEAGE: " . $mileageFormat);
                    $allTrip->total_mileage = $mileageFormat;
                    $success = $allTrip->save();
                    if($success){
                        $this->info("SUCCESSFULLY UPDATED TOTAL MILEAGE");
                    }else{
                        $this->info("FAILED!!");
                    }
                }else{
                    $this->info("NO GPS TRACKING");
                }
            }else{
                $this->info("ALREADY HAVE TOTAL MILEAGE");
            }
        }
        $this->info("END OF LOOP...");
    }
}
