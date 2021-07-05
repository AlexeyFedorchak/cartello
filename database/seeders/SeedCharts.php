<?php

namespace Database\Seeders;

use App\Charts\Constants\ChartPeriods;
use App\Charts\Constants\ChartTimeFrames;
use App\Charts\Constants\ChartTypes as ChartSlugConstants;
use App\Charts\Models\Chart;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SeedCharts extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Chart::updateOrCreate([
            'slug' => Str::slug('Brand+Non-Brand Sessions: Year to Date - Monthly'),
        ], [
            'name' => 'Brand+Non-Brand Sessions: Year to Date - Monthly',
            'type' => ChartSlugConstants::DYNAMIC_CHART,
            'has_overview' => true,
            'source_columns' => 'brand_clicks|non_brand_clicks',
            'time_frame' => ChartTimeFrames::MONTHLY
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Brand Sessions: Year to Date - Monthly'),
        ], [
            'name' => 'Brand Sessions: Year to Date - Monthly',
            'type' => ChartSlugConstants::DYNAMIC_CHART,
            'has_overview' => true,
            'source_columns' => 'brand_clicks',
            'time_frame' => ChartTimeFrames::MONTHLY
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Non-Brand Sessions: Year to Date - Monthly'),
        ], [
            'name' => 'Non-Brand Sessions: Year to Date - Monthly',
            'type' => ChartSlugConstants::DYNAMIC_CHART,
            'has_overview' => true,
            'source_columns' => 'non_brand_clicks',
            'time_frame' => ChartTimeFrames::MONTHLY
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Non-Brand Sessions: Year to Date - Weekly'),
        ], [
            'name' => 'Non-Brand Sessions: Year to Date - Weekly',
            'type' => ChartSlugConstants::DYNAMIC_CHART,
            'has_overview' => true,
            'source_columns' => 'non_brand_clicks',
            'time_frame' => ChartTimeFrames::WEEKLY
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Non-Brand Sessions: Year to Date - Daily'),
        ], [
            'name' => 'Non-Brand Sessions: Year to Date - Daily',
            'type' => ChartSlugConstants::DYNAMIC_CHART,
            'has_overview' => true,
            'source_columns' => 'non_brand_clicks',
            'time_frame' => ChartTimeFrames::DAILY
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Non-Brand Performance YOY'),
        ], [
            'name' => 'Non-Brand Performance YOY',
            'type' => ChartSlugConstants::CHANGE_TABLE,
            'source_columns' => 'non_brand_clicks|non_brand_impressions',
            'time_frame' => ChartTimeFrames::MONTHLY
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Non-Brand Keywords - Google First Page Distribution'),
        ], [
            'name' => 'Non-Brand Keywords - Google First Page Distribution',
            'type' => ChartSlugConstants::DYNAMIC_STRUCTURE,
            'source_columns' => 'non_brand_clicks',
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Non-Brand Keywords - Google First Page Distribution (Year)'),
        ], [
            'name' => 'Non-Brand Keywords - Google First Page Distribution (Year)',
            'type' => ChartSlugConstants::STRUCTURE,
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Non-Brand Keywords - Last Month (MOM)'),
        ], [
            'name' => 'Non-Brand Keywords - Last Month (MOM)',
            'type' => ChartSlugConstants::TABLE_STRUCTURE_CHANGE,
            'time_frame' => ChartTimeFrames::MONTHLY,
            'period' => ChartPeriods::MONTH,
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Non-Brand Keywords - Last Month (YOY)'),
        ], [
            'name' => 'Non-Brand Keywords - Last Month (YOY)',
            'type' => ChartSlugConstants::TABLE_STRUCTURE_CHANGE,
            'time_frame' => ChartTimeFrames::MONTHLY,
            'period' => ChartPeriods::YEAR,
        ]);
    }
}
