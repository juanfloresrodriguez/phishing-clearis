<?php

namespace App\Http\Controllers;

use App\Models\Organization;

abstract class Controller
{
    protected function currentOrg(): Organization
    {
        $org = auth()->user()?->organization;

        if (!$org) {
            abort(503, 'No organization associated with this account.');
        }

        return $org;
    }
}
