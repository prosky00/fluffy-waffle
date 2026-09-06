<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateLastActive
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $user = $request->user();
        if ($user && !$request->is('heartbeat')) {
            // Throttle: only write if last update was > 60s ago
            if (!$user->last_active_at || $user->last_active_at->diffInSeconds(now()) > 60) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['last_active_at' => now()]);
            }
        }

        return $response;
    }
}
