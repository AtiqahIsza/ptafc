<?php

namespace App\Console\Commands;

use App\Models\Bus;
use App\Models\BusDriver;
use App\Models\Route;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RemoveInactive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'removeinactive:ri';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove Inactive Bus Driver and Route after 30 days';

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
        $currentDate = Carbon::now();
        $this->info('Current Date: ' . $currentDate);
        $this->info('Processing Inactive Bus Driver...');
        $inactiveDriver = BusDriver::where('status',2)->get();
        if(count($inactiveDriver)>0){
            foreach($inactiveDriver as $driver){
                $this->info('Driver Updated At: ' . $driver->updated_at);
                $driverName = $driver->id . ' - ' .  $driver->driver_name;
                $diff_in_days = $currentDate->diffInDays($driver->updated_at);
                if($diff_in_days>30){
                    $this->info('Removing ' . $driverName . '...');
                    $removed = BusDriver::findOrFail($driver->id);
                    $successRemove = $removed->delete();
                    if($successRemove){
                        $this->info('SUCCESSFULLY REMOVED!');
                    }
                }else{
                    $this->info($driverName . ' been inactived for ' . $diff_in_days . ' days');
                }
            }
        }else{
            $this->info('NO INACTIVE BUS DRIVER');
        }

        $this->info('Processing Inactive Bus...');
        $inactiveBuses = Bus::where('status',2)->get();
        if(count($inactiveBuses)>0){
            foreach($inactiveBuses as $bus){
                $this->info('Bus Updated At: ' . $bus->updated_at);
                $busName = $bus->bus_registration_number;
                $diff_in_days = $currentDate->diffInDays($bus->updated_at);
                if($diff_in_days>30){
                    $this->info('Removing ' . $busName . '...');
                    $removed = Bus::findOrFail($bus->id);
                    $successRemove = $removed->delete();
                    if($successRemove){
                        $this->info('SUCCESSFULLY REMOVED!');
                    }
                }else{
                    $this->info($busName . ' been inactived for ' . $diff_in_days . ' days');
                }
            }
        }else{
            $this->info('NO INACTIVE BUS');
        }
        
        $this->info('Processing Inactive Route...');
        $inactiveRoute = Route::where('status',2)->get();
        if(count($inactiveRoute)>0){
            foreach($inactiveRoute as $route){
                $routeName = $route->route_number . ' - ' . $route->route_name;
                $diff_in_days = $currentDate->diffInDays($route->updated_at);
                if($diff_in_days>30){
                    $this->info('Removing ' . $routeName . '...');
                    $removed = Route::findOrFail($route->id);
                    $successRemove = $removed->delete();
                    if($successRemove){
                        $this->info('SUCCESSFULLY REMOVED!');
                    }
                }else{
                    $this->info($routeName . ' been inactived for ' . $diff_in_days . ' days');
                }
            }
        }else{
            $this->info('NO INACTIVE ROUTE');
        }

        $this->info('COMMAND COMPLETED!');
    }
}
