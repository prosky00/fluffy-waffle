<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsHr
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isHr()) {
            abort(403, 'Nincs jogosultságod ehhez az oldalhoz.');
        }

        return $next($request);
    }
}
