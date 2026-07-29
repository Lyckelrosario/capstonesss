<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(): View
    {
        // Most requested services
        $services = DB::table('appointments as a')
            ->join('services as s', 's.id', '=', 'a.service_id')
            ->select('s.name', DB::raw('COUNT(*) as total'))
            ->groupBy('s.id', 's.name')
            ->orderByDesc('total')
            ->get();

        // Most sold product brands
        $brands = DB::table('sales as sa')
            ->join('products as p', 'p.id', '=', 'sa.product_id')
            ->select('p.brand', DB::raw('SUM(sa.quantity) as total_qty'), DB::raw('SUM(sa.total) as total_revenue'))
            ->groupBy('p.brand')
            ->orderByDesc('total_qty')
            ->get();

        // Total revenue from sales
        $totalRevenue = DB::table('sales')->sum('total');

        // Appointment conversion funnel
        $appointmentStats = [
            'total' => DB::table('appointments')->count(),
            'pending' => DB::table('appointments')->where('status', 'pending')->count(),
            'approved' => DB::table('appointments')->where('status', 'approved')->count(),
            'completed' => DB::table('appointments')->where('status', 'completed')->count(),
            'cancelled' => DB::table('appointments')->where('status', 'cancelled')->count(),
        ];

        // Conversion rate (completed / total excluding pending)
        $processedTotal = $appointmentStats['completed'] + $appointmentStats['cancelled'];
        $conversionRate = $processedTotal > 0
            ? round(($appointmentStats['completed'] / $processedTotal) * 100, 1)
            : 0;

        // Monthly appointments (last 12 months)
        $monthlyAppointments = DB::table('appointments')
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled")
            )
            ->where('created_at', '>=', now()->subYear())
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->orderByRaw('YEAR(created_at) DESC, MONTH(created_at) DESC')
            ->limit(12)
            ->get();

        // Average ratings
        $avgRatings = DB::table('feedback')
            ->select(
                DB::raw('ROUND(AVG(shop_rating), 1) as avg_shop'),
                DB::raw('ROUND(AVG(mechanic_rating), 1) as avg_mechanic'),
                DB::raw('COUNT(*) as total_reviews')
            )
            ->first();

        return view('admin.analytics', compact(
            'services',
            'brands',
            'totalRevenue',
            'appointmentStats',
            'conversionRate',
            'monthlyAppointments',
            'avgRatings'
        ));
    }
}
