<?php

namespace App\Sessions\Traits;

trait Devices
{
    protected $devices = [
        'MOBILE',
        'DESKTOP',
        'TABLET',
    ];

    /**
     * get list of used devices
     *
     * @return string[]
     */
    public function devices()
    {
        return $this->devices;
    }
}
