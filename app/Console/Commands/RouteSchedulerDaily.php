<?php

namespace App\Console\Commands;

use App\Models\RouteSchedulerMSTR;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        $copies = RouteSchedulerMSTR::select('id')->get();
        foreach($copies as $copy){
            DB::table('route_scheduler_details')->insert([
                'schedule_date' => now(),
                'route_scheduler_mstr_id' => $copy->id,
            ]);
            $this->info('Route scheduler inserting...');
        }
        $this->info('Done!');
        return 0;
    }
}
