<?php

namespace App\Data\Traits;

use App\BigQuery\IClient;
use App\Data\Models\Domains;

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

        $offset = 0;

        while(count($searchAnalyticsPureRows) > 0) {
            $lastDate = implode(',',
                explode('-', ($lastRow->date ?? $this->defaultStartDate))
            );

            $searchAnalyticsPureRows = app(IClient::class)
                ->select($this->bigQueryTable)
                ->where('where date <= CURRENT_DATE()')
                ->where('date >= DATE(' . $lastDate . ')')
                ->order('order by date ASC')
                ->limit($this->perQuery, $offset)
                ->get();

            $offset += $this->perQuery;

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

