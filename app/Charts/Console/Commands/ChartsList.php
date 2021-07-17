<?php

namespace App\Charts\Console\Commands;

use App\Charts\Models\Chart;
use Illuminate\Console\Command;

class ChartsList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charts:list';

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
        foreach (Chart::all() as $chart)
            $this->info($chart->id . ': ' . $chart->type . ' [*** ' . $chart->slug  . ' ***]');
    }
}
