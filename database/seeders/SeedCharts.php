<?php

namespace Database\Seeders;

use App\ChartTypes\Constants\ChartTypes as ChartSlugConstants;
use App\ChartTypes\Models\Chart;
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
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Brand Sessions: Year to Date - Monthly'),
        ], [
            'name' => 'Brand Sessions: Year to Date - Monthly',
            'type' => ChartSlugConstants::DYNAMIC_CHART,
            'has_overview' => true,
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Non-Brand Sessions: Year to Date - Monthly'),
        ], [
            'name' => 'Non-Brand Sessions: Year to Date - Monthly',
            'type' => ChartSlugConstants::DYNAMIC_CHART,
            'has_overview' => true,
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Non-Brand Performance YOY'),
        ], [
            'name' => 'Non-Brand Performance YOY',
            'type' => ChartSlugConstants::CHANGE_TABLE,
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Non-Brand Keywords - Google First Page Distribution'),
        ], [
            'name' => 'Non-Brand Keywords - Google First Page Distribution',
            'type' => ChartSlugConstants::DYNAMIC_STRUCTURE,
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
        ]);

        Chart::updateOrCreate([
            'slug' => Str::slug('Non-Brand Keywords - Last Month (YOY)'),
        ], [
            'name' => 'Non-Brand Keywords - Last Month (YOY)',
            'type' => ChartSlugConstants::TABLE_STRUCTURE_CHANGE,
        ]);
    }
}
