<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminAppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('appointments as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('services as s', 's.id', '=', 'a.service_id')
            ->join('mechanics as m', 'm.id', '=', 'a.mechanic_id')
            ->where('a.status', '!=', 'completed')
            ->select('a.*', 'u.name as client', 's.name as service', 'm.name as mechanic');

        // Search by client name, service, or mechanic
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('u.name', 'like', "%{$search}%")
                  ->orWhere('s.name', 'like', "%{$search}%")
                  ->orWhere('m.name', 'like', "%{$search}%")
                  ->orWhere('a.vehicle_brand', 'like', "%{$search}%")
                  ->orWhere('a.plate_number', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('a.status', $status);
        }

        $appointments = $query->orderByDesc('a.created_at')->paginate(15);

        return view('admin.appointments', compact('appointments'));
    }

    public function approve(int $id, Request $request, NotificationService $notifications, ActivityLogger $logger): RedirectResponse
    {
        $appointment = DB::table('appointments')->where('id', $id)->first();
        if (! $appointment || $appointment->status !== 'pending') {
            return back()->with('error', 'Only pending appointments can be approved.');
        }

        DB::table('appointments')->where('id', $id)->update(['status' => 'approved', 'updated_at' => now()]);

        // Send in-app notification to client
        $notifications->send(
            $appointment->user_id,
            'appointment_approved',
            'Appointment Approved',
            "Your appointment scheduled for {$appointment->appointment_date} has been approved.",
            route('client.dashboard')
        );

        // Log activity
        $logger->log('appointment.approved', 'appointment', $id, "Approved appointment #{$id}", $request);

        return back()->with('success', 'Appointment approved. Client has been notified.');
    }

    public function cancel(int $id, Request $request, NotificationService $notifications, ActivityLogger $logger): RedirectResponse
    {
        $appointment = DB::table('appointments')->where('id', $id)->whereIn('status', ['pending', 'approved'])->first();
        if (! $appointment) {
            return back()->with('error', 'The appointment cannot be cancelled.');
        }

        DB::table('appointments')->where('id', $id)->update(['status' => 'cancelled', 'updated_at' => now()]);

        // Send in-app notification to client
        $notifications->send(
            $appointment->user_id,
            'appointment_cancelled',
            'Appointment Cancelled',
            "Your appointment scheduled for {$appointment->appointment_date} has been cancelled. Please book a new one.",
            route('client.appointment')
        );

        $logger->log('appointment.cancelled', 'appointment', $id, "Cancelled appointment #{$id}", $request);

        return back()->with('success', 'Appointment cancelled. Client has been notified.');
    }

    public function complete(int $id, Request $request, NotificationService $notifications, ActivityLogger $logger): RedirectResponse
    {
        $appointment = DB::table('appointments')->where('id', $id)->where('status', 'approved')->first();
        if (! $appointment) {
            return back()->with('error', 'Only approved appointments can be completed.');
        }

        DB::table('appointments')->where('id', $id)->update(['status' => 'completed', 'updated_at' => now()]);

        // Send in-app notification to client
        $notifications->send(
            $appointment->user_id,
            'appointment_completed',
            'Service Completed',
            "Your {$appointment->appointment_date} service is complete. Please rate your experience.",
            route('client.feedback')
        );

        $logger->log('appointment.completed', 'appointment', $id, "Completed appointment #{$id}", $request);

        return redirect()->route('admin.archive')->with('success', 'Job completed and moved to the archive.');
    }

    public function archive(Request $request): View
    {
        $query = DB::table('appointments as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('services as s', 's.id', '=', 'a.service_id')
            ->join('mechanics as m', 'm.id', '=', 'a.mechanic_id')
            ->leftJoin('feedback as f', 'f.appointment_id', '=', 'a.id')
            ->where('a.status', 'completed')
            ->select('a.*', 'u.name as client', 's.name as service', 'm.name as mechanic', 'f.shop_rating', 'f.mechanic_rating');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('u.name', 'like', "%{$search}%")
                  ->orWhere('s.name', 'like', "%{$search}%")
                  ->orWhere('m.name', 'like', "%{$search}%");
            });
        }

        $appointments = $query->orderByDesc('a.updated_at')->paginate(15);

        return view('admin.archive', compact('appointments'));
    }
}
