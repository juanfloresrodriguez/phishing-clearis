<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;

class EnsureHasOrganization
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Si el usuario no tiene organización, asignarle la primera disponible
        // (útil cuando el seed crea la org después del usuario o en entornos de desarrollo)
        if (!$user->organization_id) {
            $org = Organization::first();
            if ($org) {
                $user->update(['organization_id' => $org->id]);
                $user->refresh();
            } else {
                // No hay organización: redirigir a una página de error informativa
                if (!$request->is('setup*') && !$request->routeIs('logout')) {
                    return redirect()->route('dashboard')->withErrors([
                        'org' => 'No organization found. Run: php artisan db:seed',
                    ]);
                }
            }
        }

        return $next($request);
    }
}
