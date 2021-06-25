<?php

namespace App\Sessions\Traits;

trait BrandSessionsRegex
{
    protected $regex = [
        'نون|noon',
        'نمشي|nam|nashmi|nashimi|nemchi|nmshi',
        'pnp|pick|pay|smart|pik',
        'game|makro|builders',
    ];

    /**
     * get keywords list
     *
     * @return array
     */
    public function brandKeywords(): array
    {
        return explode('|', implode('|', $this->regex));
    }
}
