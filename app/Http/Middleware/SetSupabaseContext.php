<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetSupabaseContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // For Laravel operations, use service_role to bypass RLS
        // This is necessary because Laravel needs full database access
        // for authentication, session management, etc.
        
        try {
            // Check if we're using PostgreSQL/Supabase
            if (config('database.default') === 'pgsql' || config('database.default') === 'supabase') {
                // Try to set the role to service_role to bypass RLS
                // Note: This may not work with connection poolers, but the after_connect
                // hook in database config should handle it
                DB::statement("SET LOCAL role TO service_role");
            }
        } catch (\Exception $e) {
            // If this fails, it means:
            // 1. We're not using Supabase/PostgreSQL
            // 2. RLS is not configured
            // 3. Role is already set via connection config
            // Continue anyway - the after_connect hook should have handled it
        }
        
        return $next($request);
    }
}
