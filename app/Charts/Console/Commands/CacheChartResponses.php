<?php

namespace App\Charts\Console\Commands;

use App\Charts\Models\Chart;
use Illuminate\Console\Command;

class CacheChartResponses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:chart-responses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache responses from Big Query';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (Chart::all() as $chart) {
            try {
                $chart->getData();
            } catch (\Throwable $e) {}
        }
    }
}
