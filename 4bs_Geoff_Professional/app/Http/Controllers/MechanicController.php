<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MechanicController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('mechanics')->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('specialty', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $mechanics = $query->paginate(20);

        return view('admin.mechanics', compact('mechanics'));
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'specialty' => ['required', 'string', 'max:160'],
            'experience' => ['nullable', 'string', 'max:120'],
        ]);

        DB::table('mechanics')->insert([...$data, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        $logger->log('mechanic.added', 'mechanic', null, "Added mechanic {$data['name']}", $request);

        return back()->with('success', 'Mechanic added.');
    }
}
