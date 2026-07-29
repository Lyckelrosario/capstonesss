<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@4bs.test'],
            ['name' => '4BS Admin', 'phone' => '09000000000', 'password' => 'admin123', 'role' => 'admin', 'email_verified_at' => now(), 'onboarding_completed_at' => now()]
        );

        User::updateOrCreate(
            ['email' => 'client@4bs.test'],
            ['name' => 'Sample Client', 'phone' => '09123456789', 'password' => 'client123', 'role' => 'client', 'email_verified_at' => now()]
        );

        foreach ([
            ['name' => 'Mark Dela Cruz', 'specialty' => 'Brake and underchassis', 'experience' => '5 years', 'status' => 'active'],
            ['name' => 'Joel Santos', 'specialty' => 'Engine tune-up', 'experience' => '7 years', 'status' => 'active'],
            ['name' => 'Rico Reyes', 'specialty' => 'Electrical diagnosis', 'experience' => '4 years', 'status' => 'active'],
        ] as $mechanic) {
            DB::table('mechanics')->updateOrInsert(
                ['name' => $mechanic['name']],
                [...$mechanic, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        foreach ([
            ['name' => 'Brake Inspection', 'description' => 'Brake pads, fluid, pedal response and safety check.', 'price' => 450, 'duration_minutes' => 60, 'status' => 'active'],
            ['name' => 'Oil Change', 'description' => 'Oil drain, replacement and basic engine check.', 'price' => 650, 'duration_minutes' => 45, 'status' => 'active'],
            ['name' => 'Engine Check-up', 'description' => 'Basic diagnosis for minor engine issues.', 'price' => 800, 'duration_minutes' => 90, 'status' => 'active'],
        ] as $service) {
            DB::table('services')->updateOrInsert(
                ['name' => $service['name']],
                [...$service, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        foreach ([
            ['brand' => 'Motul', 'name' => 'Motul Oil 10W-40', 'category' => 'Engine Oil', 'quantity' => 40, 'unit' => 'bottle', 'price' => 380, 'status' => 'available'],
            ['brand' => 'Honda', 'name' => 'Brake Fluid DOT 4', 'category' => 'Brake Fluid', 'quantity' => 25, 'unit' => 'bottle', 'price' => 220, 'status' => 'available'],
            ['brand' => 'NGK', 'name' => 'Spark Plug', 'category' => 'Ignition', 'quantity' => 60, 'unit' => 'pcs', 'price' => 180, 'status' => 'available'],
        ] as $product) {
            DB::table('products')->updateOrInsert(
                ['brand' => $product['brand'], 'name' => $product['name']],
                [...$product, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
