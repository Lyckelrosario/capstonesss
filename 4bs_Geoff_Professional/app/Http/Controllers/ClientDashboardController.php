<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClientDashboardController extends Controller
{
    public function __invoke(Request $request, NotificationService $notifications): View
    {
        $userId = $request->user()->id;

        $appointments = DB::table('appointments as a')
            ->join('services as s', 's.id', '=', 'a.service_id')
            ->join('mechanics as m', 'm.id', '=', 'a.mechanic_id')
            ->where('a.user_id', $userId)
            ->select('a.*', 's.name as service', 'm.name as mechanic')
            ->orderByDesc('a.created_at')
            ->limit(8)
            ->get();

        $upcoming = DB::table('appointments')
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('appointment_date', '>=', now()->toDateString())
            ->count();

        $toRate = DB::table('appointments as a')
            ->leftJoin('feedback as f', 'f.appointment_id', '=', 'a.id')
            ->where('a.user_id', $userId)
            ->where('a.status', 'completed')
            ->whereNull('f.id')
            ->count();

        $total = DB::table('appointments')->where('user_id', $userId)->count();
        $unreadNotifications = $notifications->unreadCount($userId);

        // Recent notifications
        $recentNotifications = Notification::forUser($userId)
            ->latest()
            ->limit(5)
            ->get();

        return view('client.dashboard', compact(
            'appointments', 'total', 'toRate', 'upcoming',
            'unreadNotifications', 'recentNotifications'
        ));
    }
}
