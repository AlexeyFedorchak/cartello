<?php

namespace App\Http\Controllers;

use App\Charts\Models\CachedDomainList;
use App\Http\Requests\ValidateGetDomainsRequest;

class GetDomainsAPIController extends Controller
{
    public function get(ValidateGetDomainsRequest $request)
    {
        return CachedDomainList::orderBy('clicks', 'DESC')->get();
    }
}
