<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone', 50)->nullable();
                $table->string('password');
                $table->enum('role', ['admin', 'client'])->default('client');
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mechanics')) {
            Schema::create('mechanics', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('specialty');
                $table->string('experience')->nullable();
                $table->string('photo')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->unsignedInteger('duration_minutes')->default(60);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('brand');
                $table->string('name');
                $table->string('category');
                $table->unsignedInteger('quantity')->default(0);
                $table->string('unit', 50)->default('pcs');
                $table->decimal('price', 10, 2);
                $table->string('image')->nullable();
                $table->enum('status', ['available', 'unavailable'])->default('available');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('mechanic_id')->constrained('mechanics')->cascadeOnDelete();
                $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
                $table->string('vehicle_brand');
                $table->string('vehicle_model');
                $table->string('plate_number')->nullable();
                $table->date('appointment_date');
                $table->time('appointment_time');
                $table->text('issue_description')->nullable();
                $table->text('ai_diagnosis')->nullable();
                $table->enum('status', ['pending', 'approved', 'completed', 'cancelled'])->default('pending');
                $table->timestamps();
                $table->index('user_id');
                $table->index('service_id');
            });
        }

        if (! Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('total', 10, 2);
                $table->date('sold_at');
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('feedback')) {
            Schema::create('feedback', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('mechanic_id')->constrained('mechanics')->cascadeOnDelete();
                $table->foreignId('appointment_id')->unique()->constrained('appointments')->cascadeOnDelete();
                $table->unsignedTinyInteger('shop_rating');
                $table->unsignedTinyInteger('mechanic_rating');
                $table->text('comment');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('products');
        Schema::dropIfExists('services');
        Schema::dropIfExists('mechanics');
        Schema::dropIfExists('users');
    }
};
