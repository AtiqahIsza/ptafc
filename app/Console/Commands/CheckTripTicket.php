<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use App\Models\TripDetail;
use App\Models\RouteSchedulerMSTR;
use App\Models\Bus;
use App\Models\Route;
use App\Models\BusDriver;
use App\Models\TicketSalesTransaction;
use App\Models\BusStand;
use App\Models\Stage;

class CheckTripTicket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tripticks:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run to check trips and tickets file daily';

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

        //Check Trip Files
        $tripFiles = Storage::allFiles('trips');
        if(count($tripFiles)>0){
            foreach($tripFiles as $tripFile){
                $modified = Storage::lastModified($tripFile);
                $date_modified = date('Y-m-d', $modified);

                if($yesterdayDate==$date_modified){
                    $this->info('Checking trip files...');
                    $path = Storage::path($tripFile);
                    $reads = file($path);
                    foreach ($reads as $read) {
                        $parse = str_getcsv($read, ',');
                        $newTrip = new TripDetail();

                        $checkTrip = TripDetail::where('trip_number', $parse[0])->first();
                        if(empty($checkTrip)){
                            $newTrip->trip_number = $parse[0];
                            $newTrip->start_trip = $parse[1];
                            $newTrip->end_trip = $parse[2];

                            $checkSchedule = RouteSchedulerMSTR::where('id', $parse[3])->first();
                            if(!empty($checkSchedule)){
                                $newTrip->route_schedule_mstr_id = $parse[3];
                            }
                            $checkBus = Bus::where('id', $parse[4])->first();
                            if(!empty($checkBus)){
                                $newTrip->bus_id = $parse[4];
                            }
                            $checkRoute = Route::where('id', $parse[5])->first();
                            if(!empty($checkRoute)){
                                $newTrip->route_id = $parse[5];
                            }
                            $checkDriver = BusDriver::where('id', $parse[6])->first();
                            if(!empty($checkDriver)){
                                $newTrip->driver_id = $parse[6];
                            }

                            $newTrip->total_adult = $parse[7];
                            $newTrip->total_concession = $parse[8];
                            $newTrip->total_adult_amount = $parse[9];
                            $newTrip->total_concession_amount = $parse[10];
                            $newTrip->total_mileage = $parse[11];
                            $newTrip->trip_code = $parse[12];
                            $newTrip->upload_date = Carbon::now();
                            $successSave = $newTrip->save();
                            if($successSave){
                                $this->info('Entering missed trip data..');
                            }
                        }else{
                            $this->info('Ignored, existed trip data');
                        }
                    }
                }
            }
        }

         //Check Ticket Files
         $ticketFiles = Storage::allFiles('tickets');
         if(count($ticketFiles)>0){
            foreach($ticketFiles as $ticketFile){
                $modified = Storage::lastModified($ticketFile);
                $date_modified = date('Y-m-d', $modified);

                if($yesterdayDate==$date_modified){
                    $this->info('Checking tickets files...');
                    $path = Storage::path($ticketFile);
                    $reads = file($path);
                    foreach ($reads as $read) {
                        $parse = str_getcsv($read, ',');
                        $newTicket = new TicketSalesTransaction();
            
                        $getTripID = TripDetail::where('trip_number', $parse[0])->first();
                        if(!empty($getTripID)){
            
                            $checkTicket = TicketSalesTransaction::where('trip_number',$parse[0])->where('ticket_number', $parse[1])->first();
                            if(empty($checkTicket)){   
                                $newTicket->trip_id = $getTripID->id;
                                $newTicket->trip_number = $parse[0];
                                $newTicket->ticket_number = $parse[1];
            
                                $checkBusStand = BusStand::where('id', $parse[2])->first();
                                if(!empty($checkBusStand)){
                                    $newTicket->bus_stand_id = $parse[2];
                                }
            
                                $checkFromStage = Stage::where('id', $parse[3])->first();
                                $checkToStage = Stage::where('id', $parse[4])->first();
                                if(!empty($checkFromStage)){
                                    $newTicket->fromstage_stage_id = $parse[3];
                                }
                                if(!empty($checkToStage)){
                                    $newTicket->tostage_stage_id = $parse[4];
                                }
                                $newTicket->passenger_type = $parse[5];
                                $newTicket->amount = $parse[6];
                                $newTicket->actual_amount = $parse[7];
                                $newTicket->fare_type = $parse[8];
                                $newTicket->latitude = $parse[9];
                                $newTicket->longitude = $parse[10];
                                $newTicket->sales_date = $parse[11];
                                $newTicket->upload_date = Carbon::now();
            
                                $successSave = $newTicket->save();
                                if($successSave){
                                    $this->info('Entering missed ticket data..');
                                }
                            }else{
                                $this->info('Ignored, existed ticket data');
                            }
                        }else{
                            $this->info('Trip number not exist');
                        }
                    }
                }
            }
         }
    }
}
