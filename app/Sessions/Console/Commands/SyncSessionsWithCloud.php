<?php

namespace App\Sessions\Console\Commands;

use App\BigQuery\IClient;
use App\BigQuery\Traits\BigQueryTimeFormat;
use App\Sessions\Models\Session;
use App\Sessions\Traits\BrandSessionsRegex;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncSessionsWithCloud extends Command
{
    use BrandSessionsRegex, BigQueryTimeFormat;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync sessions data from Big Query cloud';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . env('GOOGLE_APPLICATION_CREDENTIALS'));

        $dates = app(IClient::class)
            ->select('searchanalytics', ['date'])
            ->where('date <= CURRENT_DATE()')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        foreach ($dates as $date) {
            if (Session::where('date', $date['date'])->exists() && Carbon::parse($date['date'])->diffInDays(now()) > 1)
                continue;

            $date = $this->switchDateString($date['date']);

            $query = app(IClient::class)
                ->select('searchanalytics', ['SUM(clicks) as count_clicks', 'SUM(impressions) as count_impressions'])
                ->where('date = DATE(' . $date . ')');

            $totalCountsData = $query->get()[0];

            $query->openGroupCondition();
            foreach ($this->brandKeywords() as $keyword)
                $query->where('query like "%' . $keyword . '%"', 'OR');

            $query->closeGroupCondition();

            $brandCountsData = $query->get()[0];

            $date = $this->switchDateString($date, ['-', ',']);
            Session::updateOrCreate(['date' => $date], [
                'brand_clicks' => $brandCountsData['count_clicks'],
                'brand_impressions' => $brandCountsData['count_impressions'],
                'total_clicks' => $totalCountsData['count_clicks'],
                'total_impressions' => $totalCountsData['count_impressions'],
                'non_brand_clicks' => $totalCountsData['count_clicks'] - $brandCountsData['count_clicks'],
                'non_brand_impressions' => $totalCountsData['count_impressions'] - $brandCountsData['count_impressions'],
            ]);

            $this->info('Processed date: ' . $date);
        }
    }
}
