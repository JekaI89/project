<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('pravo:44') or ->middleware('pravo:87,2') (pravo_id, podr_id)
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $pravoId, ?string $podrId = null): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $podr = $podrId !== null ? (int) $podrId : -1;

        if (! $user->hasPravo((int) $pravoId, $podr)) {
            abort(403, 'Недостаточно прав (pravo '.$pravoId.').');
        }

        return $next($request);
    }
}
