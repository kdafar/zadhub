<?php

namespace App\Multitenancy;

use App\Models\Provider;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class ProviderFromRouteTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        // expects route model binding: {provider} resolves to App\Models\Provider
        $provider = $request->route()?->parameter('provider');

        return $provider instanceof Provider ? $provider : null;
    }
}
