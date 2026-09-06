<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupervisorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (!$user || (!$user->is_admin && !$user->is_supervisor)) {
            abort(403, 'Nincs jogosultságod.');
        }

        return $next($request);
    }
}
