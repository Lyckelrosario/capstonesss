<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $appointments = DB::table('appointments as a')
            ->join('services as s', 's.id', '=', 'a.service_id')
            ->join('mechanics as m', 'm.id', '=', 'a.mechanic_id')
            ->leftJoin('feedback as f', 'f.appointment_id', '=', 'a.id')
            ->where('a.user_id', $request->user()->id)
            ->where('a.status', 'completed')
            ->whereNull('f.id')
            ->select('a.*', 's.name as service', 'm.name as mechanic')
            ->get();

        return view('client.feedback', compact('appointments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'shop_rating' => ['required', 'integer', 'between:1,5'],
            'mechanic_rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:3000'],
        ]);

        $appointment = DB::table('appointments')
            ->where('id', $data['appointment_id'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->first();

        abort_unless($appointment, 422, 'The appointment cannot be rated.');

        try {
            DB::table('feedback')->insert([
                'user_id' => $request->user()->id,
                'mechanic_id' => $appointment->mechanic_id,
                'appointment_id' => $appointment->id,
                'shop_rating' => $data['shop_rating'],
                'mechanic_rating' => $data['mechanic_rating'],
                'comment' => $data['comment'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException) {
            return back()->with('error', 'Feedback has already been submitted for this appointment.');
        }

        return back()->with('success', 'Thank you for your feedback.');
    }
}
