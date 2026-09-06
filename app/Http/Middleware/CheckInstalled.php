<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckInstalled
{
    public function handle(Request $request, Closure $next)
    {
        $installed = file_exists(storage_path('installed.lock'));

        if (!$installed && !$request->is('install', 'install/*')) {
            return redirect('/install');
        }

        if ($installed && $request->is('install', 'install/*')) {
            return redirect('/');
        }

        return $next($request);
    }
}
