<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckInstalled
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$this->isInstalled()) {
            return redirect()->route('install.index');
        }
        return $next($request);
    }

    public static function isInstalled(): bool
    {
        return file_exists(storage_path('installed'));
    }
}
