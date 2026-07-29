<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('services')->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $services = $query->paginate(20);

        return view('admin.services', compact('services'));
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
        ]);

        DB::table('services')->insert([...$data, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        $logger->log('service.added', 'service', null, "Added service {$data['name']}", $request);

        return back()->with('success', 'Service added.');
    }
}
