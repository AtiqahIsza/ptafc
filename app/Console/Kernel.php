<?php

namespace App\Console;

use App\Models\RouteSchedulerMSTR;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\RouteSchedulerDaily::class,
        Commands\CheckTripTicket::class,
        Commands\CheckTripTicketOpt::class
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $filePath = 'storage/logs/schedule.log';
        $schedule->command('routeschedule:daily')->dailyAt('00:00')->appendOutputTo($filePath);
        //$schedule->command('routeschedule:opt')->dailyAt('13:12')->appendOutputTo($filePath);
        $schedule->command('tripticks:check')->dailyAt('00:10')->appendOutputTo($filePath);
        //$schedule->command('tripticksOpt:check')->dailyAt('10:22')->appendOutputTo($filePath);
        //$schedule->command('tripticks:check')->everyMinute()->appendOutputTo($filePath);
        //$schedule->command('routeschedule:daily')->everyMinute()->appendOutputTo($filePath);
        /**
         * 2 option to run:
         * 1. 0 0 * * * cd /var/www/your-project && php artisan schedule:run >> /dev/null 2>&1
         * 2. cd /var/www/your-project && php artisan routeschedule:weekly >> /dev/null 2>&1
         *
         */
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
