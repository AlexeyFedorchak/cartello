<?php

namespace App\Charts\Console\Commands;

use App\Charts\Models\Chart;
use Illuminate\Console\Command;

class CacheSpecificChart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:chart';

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
        shell_exec('export GOOGLE_APPLICATION_CREDENTIALS="/home/forge/edgytech.space/normandy-api-d4370f73211d.json"');

        $chartId = $this->ask('Chart id?');
        $chart = Chart::where('id', $chartId)->first();

        if (!$chart) {
            $this->error('No charts found!');
            die();
        }

        $this->info('Processing chart: ' . $chart->slug);

        try {
            $chart->getData();
            $this->info('Done: ' . $chart->id);
        } catch (\Throwable $e) {
            $this->error('Error with processing: ' . $chart->id . ' [' . $e->getMessage() . ']');
        }
    }
}
