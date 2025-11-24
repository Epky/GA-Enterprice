<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleRedirectMiddleware
{
    /**
     * Handle an incoming request.
     * Redirects users to their appropriate dashboard if they try to access wrong areas
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        /** @var User $user */
        $user = Auth::user();
        $currentRoute = $request->route()->getName();

        // Redirect users trying to access wrong dashboards
        if ($currentRoute === 'admin.dashboard' && !$user->isAdmin()) {
            return $this->redirectToUserDashboard($user);
        }

        if ($currentRoute === 'staff.dashboard' && !$user->isStaff()) {
            return $this->redirectToUserDashboard($user);
        }

        if ($currentRoute === 'customer.dashboard' && !$user->isCustomer()) {
            return $this->redirectToUserDashboard($user);
        }

        // Check if user is trying to access admin routes without admin role
        if (str_starts_with($currentRoute, 'admin.') && !$user->isAdmin()) {
            return $this->redirectToUserDashboard($user);
        }

        // Check if user is trying to access staff routes without staff role
        if (str_starts_with($currentRoute, 'staff.') && !$user->isStaff()) {
            return $this->redirectToUserDashboard($user);
        }

        // Check if user is trying to access customer routes without customer role
        if (str_starts_with($currentRoute, 'customer.') && !$user->isCustomer()) {
            return $this->redirectToUserDashboard($user);
        }

        return $next($request);
    }

    /**
     * Redirect user to their appropriate dashboard
     */
    private function redirectToUserDashboard(User $user)
    {
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')->with('warning', 'You have been redirected to your admin dashboard.');
        } elseif ($user->isStaff()) {
            return redirect()->route('staff.dashboard')->with('warning', 'You have been redirected to your staff dashboard.');
        } else {
            return redirect()->route('customer.dashboard')->with('warning', 'You have been redirected to your customer dashboard.');
        }
    }
}
