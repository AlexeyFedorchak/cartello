<?php

namespace App\Http\Controllers\ConsoleAPI;

use App\Http\Controllers\Controller;

abstract class ConsoleAPIController extends Controller
{
    public function __construct()
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . env('GOOGLE_APPLICATION_CREDENTIALS'));
    }
}
