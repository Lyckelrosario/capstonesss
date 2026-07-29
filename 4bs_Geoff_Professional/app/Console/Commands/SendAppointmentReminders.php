<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Send in-app reminders for appointments happening tomorrow';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $appointments = DB::table('appointments')
            ->join('users', 'users.id', '=', 'appointments.user_id')
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->join('mechanics', 'mechanics.id', '=', 'appointments.mechanic_id')
            ->where('appointments.appointment_date', $tomorrow)
            ->whereIn('appointments.status', ['pending', 'approved'])
            ->select(
                'appointments.id',
                'appointments.user_id',
                'appointments.appointment_date',
                'appointments.appointment_time',
                'services.name as service_name',
                'mechanics.name as mechanic_name'
            )
            ->get();

        $count = 0;
        foreach ($appointments as $appointment) {
            // Check if a reminder was already sent today
            $alreadySent = Notification::where('user_id', $appointment->user_id)
                ->where('type', 'appointment_reminder')
                ->whereDate('created_at', today())
                ->exists();

            if ($alreadySent) {
                continue;
            }

            Notification::create([
                'user_id' => $appointment->user_id,
                'type' => 'appointment_reminder',
                'title' => 'Appointment Reminder',
                'body' => "Your {$appointment->service_name} with {$appointment->mechanic_name} is scheduled for tomorrow ({$appointment->appointment_date}) at {$appointment->appointment_time}.",
                'action_url' => route('client.dashboard'),
            ]);

            $count++;
        }

        $this->info("Sent {$count} appointment reminders for tomorrow's bookings.");

        return self::SUCCESS;
    }
}
