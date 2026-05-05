<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckNotInstalled
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (CheckInstalled::isInstalled()) {
            return redirect('/');
        }
        return $next($request);
    }
}
