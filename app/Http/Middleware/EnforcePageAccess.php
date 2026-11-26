<?php

namespace App\Http\Middleware;

use App\Support\PageAccess;
use Closure;
use Illuminate\Http\Request;

class EnforcePageAccess
{
    public function handle(Request $request, Closure $next)
    {
        $page = PageAccess::pageForRoute($request->route()?->getName());
        if ($page !== null) {
            abort_unless($request->user() && PageAccess::allows($request->user(), $page), 403);
        }
        return $next($request);
    }
}
