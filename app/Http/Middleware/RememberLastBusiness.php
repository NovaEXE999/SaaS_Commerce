<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Recuerda el último negocio utilizado en una cookie de larga duración.
 *
 * La página pública (/) la lee para mostrar la landing correspondiente
 * al tipo de negocio (barberia, tienda, papeleria) después del logout.
 */
class RememberLastBusiness
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $businessId = $request->user()?->business_id;

        if ($businessId) {
            Cookie::queue('last_business_id', (string) $businessId, 525600);
        }

        return $next($request);
    }
}
