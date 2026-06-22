<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ThrottleBookingLots
{
    public function handle(Request $request, Closure $next): Response
    {
        $cache = cache()->store($this->resolveStore());
        $limit = max(1, (int) config('booking.lots_rate_limit_per_minute', 30));
        $key = $this->cacheKey($request);
        $ttlSeconds = 60;

        if (! $cache->add($key, 0, now()->addSeconds($ttlSeconds))) {
            $current = (int) $cache->get($key, 0);
            if ($current >= $limit) {
                return $this->tooManyRequests($limit);
            }
        }

        $current = (int) $cache->increment($key);
        if ($current === 1) {
            $cache->put($key, 1, now()->addSeconds($ttlSeconds));
        }

        if ($current > $limit) {
            return $this->tooManyRequests($limit);
        }

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $limit - $current));

        return $response;
    }

    private function resolveStore(): string
    {
        $store = (string) config('booking.lots_rate_limit_store', 'file');

        try {
            /** @var Repository $repository */
            $repository = cache()->store($store);
            $repository->getStore();

            return $store;
        } catch (Throwable) {
            return 'file';
        }
    }

    private function cacheKey(Request $request): string
    {
        return implode(':', [
            'booking',
            'lots',
            'throttle',
            sha1(implode('|', [
                (string) $request->ip(),
                substr((string) $request->userAgent(), 0, 120),
            ])),
        ]);
    }

    private function tooManyRequests(int $limit): JsonResponse
    {
        return response()->json([
            'message' => 'Terlalu banyak permintaan. Silakan coba lagi sebentar.',
        ], 429, [
            'Retry-After' => '60',
            'X-RateLimit-Limit' => (string) $limit,
            'X-RateLimit-Remaining' => '0',
        ]);
    }
}
