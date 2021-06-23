<?php

namespace App\Console\Commands;

use App\Data\Models\Domains;
use App\Data\Models\SearchAnalytics;
use App\Data\Models\SearchAnalytics2;
use App\Data\Models\SearchAnalyticsExtract;
use Illuminate\Console\Command;

class SyncWithCloud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from Big Query cloud';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting..');

        (new Domains())->syncWithCloud();
        $this->info('Domains are synced');

        (new SearchAnalytics())->syncWithCloud();
        $this->info('SearchAnalytics is synced');

        (new SearchAnalytics2())->syncWithCloud();
        $this->info('SearchAnalytics2 is synced');

        (new SearchAnalyticsExtract())->syncWithCloud();
        $this->info('SearchAnalyticsExtract is synced');

        $this->info('done');
    }
}
