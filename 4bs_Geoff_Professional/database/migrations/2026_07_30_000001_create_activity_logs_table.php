<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 100); // appointment.approved, appointment.cancelled, product.added, etc.
                $table->string('entity_type', 80)->nullable(); // appointment, product, service, mechanic
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['entity_type', 'entity_id']);
                $table->index('action');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
