<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function create(): View
    {
        return view('client.appointment', [
            'mechanics' => DB::table('mechanics')->where('status', 'active')->orderBy('name')->get(),
            'services' => DB::table('services')->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mechanic_id' => ['required', 'integer', 'exists:mechanics,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'vehicle_brand' => ['required', 'string', 'max:120'],
            'vehicle_model' => ['required', 'string', 'max:120'],
            'plate_number' => ['nullable', 'string', 'max:50'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'issue_description' => ['nullable', 'string', 'max:3000'],
            'ai_diagnosis' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            DB::transaction(function () use ($data, $request): void {
                $mechanic = DB::table('mechanics')->where('id', $data['mechanic_id'])->lockForUpdate()->first();
                abort_unless($mechanic && $mechanic->status === 'active', 422, 'The selected mechanic is unavailable.');

                $serviceActive = DB::table('services')->where('id', $data['service_id'])->where('status', 'active')->exists();
                abort_unless($serviceActive, 422, 'The selected service is unavailable.');

                $exists = DB::table('appointments')
                    ->where('mechanic_id', $data['mechanic_id'])
                    ->where('appointment_date', $data['appointment_date'])
                    ->where('appointment_time', $data['appointment_time'])
                    ->whereIn('status', ['pending', 'approved'])
                    ->exists();

                abort_if($exists, 422, 'That mechanic is already booked for the selected time.');

                DB::table('appointments')->insert([
                    ...$data,
                    'user_id' => $request->user()->id,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }, 3);
        } catch (QueryException) {
            return back()->withInput()->with('error', 'The appointment could not be saved. Please choose another time and try again.');
        }

        return redirect()->route('client.dashboard')->with('success', 'Your appointment request was submitted.');
    }

    public function reschedule(Request $request, int $id): RedirectResponse
    {
        $appointment = DB::table('appointments')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        abort_unless($appointment, 404, 'Appointment not found or cannot be rescheduled.');

        $data = $request->validate([
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'issue_description' => ['nullable', 'string', 'max:3000'],
        ]);

        try {
            DB::transaction(function () use ($data, $request, $id): void {
                $exists = DB::table('appointments')
                    ->where('id', '!=', $id)
                    ->where('mechanic_id', DB::table('appointments')->where('id', $id)->value('mechanic_id'))
                    ->where('appointment_date', $data['appointment_date'])
                    ->where('appointment_time', $data['appointment_time'])
                    ->whereIn('status', ['pending', 'approved'])
                    ->exists();

                abort_if($exists, 422, 'That slot is already taken. Please choose another time.');

                DB::table('appointments')->where('id', $id)->update([
                    'appointment_date' => $data['appointment_date'],
                    'appointment_time' => $data['appointment_time'],
                    'issue_description' => $data['issue_description'] ?? $appointment->issue_description,
                    'status' => 'pending', // Reset to pending for re-approval
                    'updated_at' => now(),
                ]);
            }, 3);
        } catch (QueryException) {
            return back()->withInput()->with('error', 'The appointment could not be rescheduled. Please try again.');
        }

        return redirect()->route('client.dashboard')->with('success', 'Your appointment has been rescheduled and sent for re-approval.');
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        $appointment = DB::table('appointments')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        abort_unless($appointment, 404, 'Appointment not found or cannot be cancelled.');

        DB::table('appointments')->where('id', $id)->update([
            'status' => 'cancelled',
            'updated_at' => now(),
        ]);

        // Notify admins
        app(NotificationService::class)->sendToAdmins(
            'appointment_cancelled',
            'Appointment Cancelled by Client',
            "{$request->user()->name} cancelled their appointment scheduled for {$appointment->appointment_date}.",
            route('admin.appointments')
        );

        return redirect()->route('client.dashboard')->with('success', 'Your appointment has been cancelled.');
    }
}
