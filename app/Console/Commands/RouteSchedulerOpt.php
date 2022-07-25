<?php

namespace App\Console\Commands;

use App\Models\RouteSchedulerDetail;
use App\Models\RouteSchedulerMSTR;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use League\CommonMark\Extension\SmartPunct\EllipsesParser;
use Illuminate\Support\Carbon;

class RouteSchedulerOpt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routeschedule:opt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run route scheduler optional';

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
        */

        $startDate = new Carbon('2022-07-01');
        $endDate = new Carbon('2022-07-22');
        $all_dates = array();

        while ($startDate->lte($endDate)) {
            $all_dates[] = $startDate->toDateString();
            $startDate->addDay();
        }
        
        $isWeekday = false;
        $isWeekend = false;
        foreach($all_dates as $all_date){
            $firstHour = new Carbon($all_date);
            $lastHour = new Carbon($all_date .'23:59:59');
            $isWeekday = $firstHour->isWeekday();
            $isWeekend =  $firstHour->isWeekend();
    
            if($isWeekday){
                $isFriday = $firstHour->format('l');
                if($isFriday=='Friday'){
                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,7])->get();
                }else{
                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [1,3,5,4,6,9])->get();
                }
    
                foreach($copies as $copy){
                    //Check existed schedule
                    $existedSchedule = RouteSchedulerDetail::whereBetween('schedule_date', [$firstHour, $lastHour])
                    ->where('route_scheduler_mstr_id', $copy->id)
                    ->first();

                    if(!$existedSchedule){
                        $new = new RouteSchedulerDetail();
                        $new->schedule_date = $all_date;
                        $new->route_scheduler_mstr_id = $copy->id;
                        $success = $new->save();
                        // DB::table('route_scheduler_details')->insert([
                        //     'schedule_date' => now(),
                        //     'route_scheduler_mstr_id' => $copy->id,
                        // ]);
                        if($success){
                            $this->info('New route scheduler in weekday/allday re-inserting...');
                        }else{
                            $this->info('New route scheduler in weekday/allday failed to re-insert');
                        }
                    }else{
                        $this->info('Existed schedule');
                    }
                }
            }
            if($isWeekend){
                $isSunday = $firstHour->format('l');
                if($isSunday=='Sunday'){
                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,10,11])->get();
                }else{
                    $copies = RouteSchedulerMSTR::whereIn('trip_type', [2,3,4,5,8,9])->get();
                }

                foreach($copies as $copy){
                    //Check existed schedule
                    $existedSchedule = RouteSchedulerDetail::whereBetween('schedule_date', [$firstHour, $lastHour])
                    ->where('route_scheduler_mstr_id', $copy->id)
                    ->first();

                    if(!$existedSchedule){
                        $new = new RouteSchedulerDetail();
                        $new->schedule_date = $all_date;
                        $new->route_scheduler_mstr_id = $copy->id;
                        $success = $new->save();
                        // DB::table('route_scheduler_details')->insert([
                        //     'schedule_date' => now(),
                        //     'route_scheduler_mstr_id' => $copy->id,
                        // ]);
                        if($success){
                            $this->info('New route scheduler in weekend/allday re-inserting...');
                        }else{
                            $this->info('New route scheduler in weekend/allday failed to re-insert');
                        }
                    }else{
                        $this->info('Existed schedule');
                    }
                }
            }
            $this->info('Done Entering Data In ' . $all_date);
        }
        $this->info('COMPLETED!');
    }
}
