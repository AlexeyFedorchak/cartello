<?php

namespace App\Data\Models;

use App\BigQuery\IClient;
use App\Data\Traits\CloudAnalytics;
use Illuminate\Database\Eloquent\Model;

class SearchAnalyticsExtract extends Model
{
    protected $table = 'search_analytics_extract';

    /**
     * big query table name
     *
     * @var string
     */
    protected $bigQueryTable = 'searchanalytics_extract';

    /**
     * rows per one query
     *
     * @var int
     */
    protected $perQuery = 1000;

    protected $fillable = [
        'domain_id',
        'domain',
        'date',
        'query',
        'device',
        'country',
        'clicks',
        'impressions',
        'position',
    ];

    /**
     * sync analytics data from cloud
     */
    public function syncWithCloud()
    {
        //start cycle
        $searchAnalyticsPureRows[] = true;

        self::truncate();

        while(count($searchAnalyticsPureRows) > 0) {
            $searchAnalyticsPureRows = app(IClient::class)
                ->select($this->bigQueryTable)
                ->where('where date is not null')
                ->order('order by date ASC')
                ->limit($this->perQuery)
                ->get();

            $domains = collect($searchAnalyticsPureRows)
                ->pluck('domains')
                ->unique()
                ->toArray();

            $domains = Domains::whereIn('domain', $domains)
                ->get(['id', 'domain']);

            $domainIdsToDomains = [];
            foreach ($domains as $domain)
                $domainIdsToDomains[$domain->domain] = $domain->id;

            foreach ($searchAnalyticsPureRows as $key => $row) {
                $row['domain_id'] = $domainIdsToDomains[$row['domain']] ?? null;
                $row['created_at'] = now();
                $row['updated_at'] = now();

                $searchAnalyticsPureRows[$key] = $row;
            }

            self::insert($searchAnalyticsPureRows);
        }
    }
}
