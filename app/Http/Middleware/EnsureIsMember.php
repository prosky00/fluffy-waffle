<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsMember
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->is_member) {
            return redirect()->route('welcome')
                ->withErrors(['member' => 'Ehhez az oldalhoz tagsági jogosultság szükséges. Nézd meg a jelentkezésed állapotát a Jelentkezéseim oldalon.']);
        }

        return $next($request);
    }
}
