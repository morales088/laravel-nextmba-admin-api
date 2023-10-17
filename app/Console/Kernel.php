<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('export:student-data')->dailyAt('15:00')->timezone('Asia/Manila')->withoutOverlapping();
        $schedule->command('students:check-course')->dailyAt('16:00')->timezone('Asia/Manila')->withoutOverlapping();

        $schedule->command('students:add-to-groups')->dailyAt('16:00')->timezone('Asia/Manila')->withoutOverlapping();
        $schedule->command('students:remove-to-groups')->dailyAt('16:00')->timezone('Asia/Manila')->withoutOverlapping();
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
