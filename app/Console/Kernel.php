<?php

namespace App\Console;

use App\Charts\Console\Commands\CacheChartResponses;
use App\Charts\Console\Commands\CacheDomainList;
use App\Charts\Console\Commands\CacheSpecificChart;
use App\Charts\Console\Commands\ChartsList;
use App\Charts\Models\CachedResponses;
use App\Sessions\Console\Commands\SyncSessionsWithCloud;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SyncSessionsWithCloud::class,
        CacheChartResponses::class,
        CacheDomainList::class,
        CacheSpecificChart::class,
        ChartsList::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
         $schedule->command('sessions:sync')
             ->withoutOverlapping()
             ->daily();

        $schedule->command('cache:chart-responses')
            ->withoutOverlapping()
            ->daily();

        $schedule->command('cache:domains')
            ->withoutOverlapping()
            ->daily();
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
