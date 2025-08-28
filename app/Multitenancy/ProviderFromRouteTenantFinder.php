<?php

namespace App\Multitenancy;

use App\Models\Provider;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class ProviderFromRouteTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        if ($request->route() && $request->route()->hasParameter('provider')) {
            // The route model binding handles the lookup by slug automatically.
            $provider = $request->route()->parameter('provider');

            if ($provider instanceof Provider) {
                return $provider;
            }
        }

        return null;
    }
}
