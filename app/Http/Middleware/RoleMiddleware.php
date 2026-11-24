<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Ensure the authenticated user has at least one of the required roles.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::user();
        if (! $user) {
            abort(403, 'User is not logged in.');
        }

        $roles = array_filter(array_map('trim', explode('|', $role)));
        if (! $user->hasAnyRole($roles)) {
            abort(403, 'User does not have the right roles.');
        }

        return $next($request);
    }
}
