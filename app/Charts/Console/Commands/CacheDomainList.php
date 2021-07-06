<?php

namespace App\Charts\Console\Commands;

use App\BigQuery\IClient;
use App\BigQuery\Traits\BigQueryTimeFormat;
use App\Charts\Models\CachedDomainList;
use Illuminate\Console\Command;

class CacheDomainList extends Command
{
    use BigQueryTimeFormat;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:domains';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache domain list';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        shell_exec('export GOOGLE_APPLICATION_CREDENTIALS="/home/forge/edgytech.space/normandy-api-d4370f73211d.json"');

        $domains = app(IClient::class)
            ->select('searchanalytics', ['SUM(clicks) as clicks', 'domain'])
            ->where('date <= CURRENT_DATE()')
            ->where('date >= DATE(' . $this->switchDateString(now()->subMonth()->format('Y-m-d')) . ')')
            ->groupBy('domain')
            ->get();

        foreach ($domains as $domain)
            CachedDomainList::updateOrCreate(['domain' => $domain['domain']], $domain);
    }
}
