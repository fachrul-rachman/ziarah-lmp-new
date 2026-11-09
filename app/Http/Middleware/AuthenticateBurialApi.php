<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBurialApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) config('booking.burial_api_key');
        abort_unless($key !== '' && hash_equals($key, (string) $request->bearerToken()), 401);

        return $next($request);
    }
}
