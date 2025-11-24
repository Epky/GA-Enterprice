<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        
        // Get recent activities
        $recentUsers = $this->getRecentUsers();
        
        // Get system health data
        $systemHealth = $this->getSystemHealth();
        
        return view('admin.dashboard', compact('stats', 'recentUsers', 'systemHealth'));
    }
    
    private function getDashboardStats()
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'staff_users' => User::where('role', 'staff')->count(),
            'customer_users' => User::where('role', 'customer')->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'new_users_this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
        ];
    }
    
    private function getRecentUsers()
    {
        return User::latest()
            ->take(10)
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at']);
    }
    
    private function getSystemHealth()
    {
        return [
            'database_status' => $this->checkDatabaseConnection(),
            'total_tables' => $this->getTotalTables(),
            'app_version' => config('app.version', '1.0.0'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }
    
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return 'Connected';
        } catch (\Exception $e) {
            return 'Disconnected';
        }
    }
    
    private function getTotalTables()
    {
        try {
            $tables = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", [config('database.connections.supabase.database')]);
            return $tables[0]->count ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
