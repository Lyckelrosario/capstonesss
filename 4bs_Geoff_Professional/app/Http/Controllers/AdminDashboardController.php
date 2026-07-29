<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $pending = DB::table('appointments')->where('status', 'pending')->count();
        $approved = DB::table('appointments')->where('status', 'approved')->count();
        $completed = DB::table('appointments')->where('status', 'completed')->count();
        $cancelled = DB::table('appointments')->where('status', 'cancelled')->count();
        $lowStock = DB::table('products')->where('quantity', '<=', 5)->count();
        $waitingChats = DB::table('chat_conversations')->where('status', 'waiting_for_admin')->count();

        // Recent activity (only query if the table exists)
        if (Schema::hasTable('activity_logs')) {
            $recentActivity = DB::table('activity_logs')
                ->join('users', 'users.id', '=', 'activity_logs.user_id')
                ->select('activity_logs.action', 'activity_logs.description', 'activity_logs.created_at', 'users.name as user_name')
                ->latest('activity_logs.created_at')
                ->limit(10)
                ->get();

            foreach ($recentActivity as $log) {
                $log->created_at = \Carbon\Carbon::parse($log->created_at);
            }
        } else {
            $recentActivity = collect();
        }

        // Total revenue this month
        $monthlyRevenue = DB::table('sales')
            ->whereMonth('sold_at', now()->month)
            ->whereYear('sold_at', now()->year)
            ->sum('total');

        // Total products in inventory
        $totalProducts = DB::table('products')->count();
        $totalMechanics = DB::table('mechanics')->where('status', 'active')->count();

        return view('admin.dashboard', compact(
            'pending', 'approved', 'completed', 'cancelled',
            'lowStock', 'waitingChats', 'recentActivity',
            'monthlyRevenue', 'totalProducts', 'totalMechanics'
        ));
    }
}
