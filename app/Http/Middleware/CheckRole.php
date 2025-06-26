<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        Log::info('🔐 Checking role access', [
            'user_id' => $request->user()->id ?? 'guest',
            'required_role' => $role,
            'user_roles' => $request->user()->getRoleNames()->toArray() ?? []
        ]);

        if (!$request->user()) {
            Log::warning('⚠️ Unauthenticated user trying to access protected route', [
                'required_role' => $role,
                'route' => $request->route()->getName() ?? $request->path()
            ]);
            
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'You must be logged in to access this resource'
            ], 401);
        }

        if (!$request->user()->hasRole($role)) {
            Log::warning('⚠️ User does not have required role', [
                'user_id' => $request->user()->id,
                'user_roles' => $request->user()->getRoleNames()->toArray(),
                'required_role' => $role,
                'route' => $request->route()->getName() ?? $request->path()
            ]);
            
            return response()->json([
                'message' => 'Forbidden',
                'error' => 'You do not have permission to access this resource'
            ], 403);
        }

        Log::info('✅ Role check passed', [
            'user_id' => $request->user()->id,
            'role' => $role
        ]);

        return $next($request);
    }
}
