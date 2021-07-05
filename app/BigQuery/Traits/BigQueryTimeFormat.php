<?php

namespace App\BigQuery\Traits;

trait BigQueryTimeFormat
{
    /**
     * parse date
     *
     * @param string $date
     * @param array|string[] $switch
     * @return string
     */
    private function switchDateString(string $date, array $switch = [',', '-']): string
    {
        return implode($switch[0], explode($switch[1], $date));
    }
}
