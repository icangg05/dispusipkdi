<?php

namespace App\Http\Middleware;

use App\Services\VisitorTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Counts the request as a website visit for the statistics page.
 */
class TrackVisitor
{
    public function __construct(protected VisitorTracker $tracker) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->isSuccessful() && $this->tracker->shouldTrack($request)) {
            try {
                $this->tracker->track($request);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $response;
    }
}
