<?php

/**
 * Class namespace
 */
namespace App\Exceptions;

/**
 * Used packages
 */
use Exception;

class NoCacheFoundForGivenChart extends Exception
{
    public function render()
    {
        return response()->json([
            'message' => 'The cache for given chart is not found! But we already making it for you.. can you try in 2 hours?!',
        ], 422);
    }
}
