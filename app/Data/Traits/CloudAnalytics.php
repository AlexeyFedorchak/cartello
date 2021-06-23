<?php

namespace App\Data\Traits;

use App\BigQuery\IClient;
use App\Data\Models\Domains;
use App\Data\Models\SearchAnalytics2;
use App\Data\Models\SearchAnalyticsExtract;

trait CloudAnalytics
{
    /**
     * if no rows found in the database, this date will be used as default
     *
     * @var string
     */
    protected $defaultStartDate = '2016-01-01';

    /**
     * sync analytics data from cloud
     */
    public function syncWithCloud()
    {
        //start cycle
        $searchAnalyticsPureRows[] = true;
        $lastRow = self::select('date')->orderBy('id', 'DESC')->first();

        self::where('date', ($lastRow->date ?? null))
            ->delete();

        while(count($searchAnalyticsPureRows) > 0) {
            $searchAnalyticsPureRows = app(IClient::class)
                ->select($this->bigQueryTable)
                ->where('where date <= CURRENT_DATE()')
                ->where('where date >= ' . ($lastRow->date ?? $this->defaultStartDate))
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

