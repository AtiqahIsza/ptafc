<?php

namespace App\Console\Commands;

use App\Models\RouteSchedulerDetail;
use App\Models\RouteSchedulerMSTR;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use League\CommonMark\Extension\SmartPunct\EllipsesParser;
use Illuminate\Support\Carbon;

class RouteSchedulerDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routeschedule:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run route scheduler daily';

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
        /* TRIP TYPE:
            1 - Weekday
            2 - Weekend
            3 - All Day
            4 - All Day Except Friday 
            5 - All Day Except Sunday
            6 - MONDAY to THURSDAY
            7 - Friday only
            8 - Saturday only
            9 - All Day Except Friday & Sunday
            10 - All Day Except Friday and Saturday
            11 - SUNDAY only
            12 - Friday & Saturday
            13 - Friday - Sunday
        */
        
        $isWeekday = false;
        $isWeekend = false;
        $currentDate = Carbon::now();

        $isWeekday = $currentDate->isWeekday();
        $isWeekend =  $currentDate->isWeekend();

        if($isWeekday){
            $isFriday = $currentDate->format('l');
            if($isFriday=='Friday'){
                $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7,12,13])->where('status', 1)->get();
            }else{
                $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9,10])->where('status', 1)->get();
            }

            foreach($copies as $copy){
                $new = new RouteSchedulerDetail();
                $new->schedule_date = now();
                $new->route_scheduler_mstr_id = $copy->id;
                $success = $new->save();
                // DB::table('route_scheduler_details')->insert([
                //     'schedule_date' => now(),
                //     'route_scheduler_mstr_id' => $copy->id,
                // ]);
                if($success){
                    $this->info('New route scheduler in weekday/allday inserting...');
                }else{
                    $this->info('New route scheduler in weekday/allday failed to insert');
                }
            }
        }
        if($isWeekend){
            $isSunday = $currentDate->format('l');
            if($isSunday=='Sunday'){
                $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11,13])->where('status', 1)->get();
            }else{
                $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9,12,13])->where('status', 1)->get();
            }
            foreach($copies as $copy){
                $new = new RouteSchedulerDetail();
                $new->schedule_date = now();
                $new->route_scheduler_mstr_id = $copy->id;
                $success = $new->save();
                // DB::table('route_scheduler_details')->insert([
                //     'schedule_date' => now(),
                //     'route_scheduler_mstr_id' => $copy->id,
                // ]);
                if($success){
                    $this->info('New route scheduler in weekend/allday inserting...');
                }else{
                    $this->info('New route scheduler in weekend/allday failed to insert');
                }
            }
        }
        $this->info('Done Inserted!');
        return 0;
    }
}
