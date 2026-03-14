<?php

namespace ChristYoga123\Vuelament\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests
{
    /**
     * Handle an incoming request.
     *
     * Inertia specifically requires returning a 303 response code when redirecting
     * after a PUT, PATCH, or DELETE request. This ensures subsequent requests
     * are treated as GET requests rather than repeating the PUT/PATCH/DELETE
     * method onto the redirected URL.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if the response is a redirect and the original request was PUT/PATCH/DELETE via Inertia
        if ($request->header('X-Inertia') 
            && in_array($request->method(), ['PUT', 'PATCH', 'DELETE'])
            && $response instanceof \Illuminate\Http\RedirectResponse 
            && $response->getStatusCode() === 302
        ) {
            $response->setStatusCode(303);
        }

        return $response;
    }
}
