<?php

namespace App\Http\Controllers;

use App\Data\Models\Domains;
use App\Http\Requests\ValidateGetDomainsRequest;

class GetDomainsAPIController extends Controller
{
    public function get(ValidateGetDomainsRequest $request)
    {
        return Domains::all();
    }
}
