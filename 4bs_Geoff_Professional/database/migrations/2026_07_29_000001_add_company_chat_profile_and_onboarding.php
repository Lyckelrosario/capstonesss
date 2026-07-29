<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'remember_token')) {
            Schema::table('users', fn (Blueprint $table) => $table->rememberToken());
        }
        if (! Schema::hasColumn('users', 'avatar_path')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('avatar_path')->nullable()->after('role'));
        }
        if (! Schema::hasColumn('users', 'onboarding_completed_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->timestamp('onboarding_completed_at')->nullable()->after('email_verified_at'));
        }

        if ($this->indexExists('appointments', 'unique_mechanic_slot')) {
            Schema::table('appointments', fn (Blueprint $table) => $table->dropUnique('unique_mechanic_slot'));
        }
        if (! $this->indexExists('appointments', 'appointments_slot_status_idx')) {
            Schema::table('appointments', fn (Blueprint $table) => $table->index(
                ['mechanic_id', 'appointment_date', 'appointment_time', 'status'],
                'appointments_slot_status_idx'
            ));
        }

        if (! Schema::hasTable('chat_conversations')) {
            Schema::create('chat_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['ai', 'waiting_for_admin', 'live', 'closed'])->default('ai');
                $table->string('subject')->default('Customer support');
                $table->timestamp('live_requested_at')->nullable();
                $table->timestamp('claimed_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamps();
                $table->index(['status', 'last_message_at']);
            });
        }

        if (! Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
                $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('sender_type', ['user', 'ai', 'admin', 'system']);
                $table->text('body');
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['conversation_id', 'id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');

        if ($this->indexExists('appointments', 'appointments_slot_status_idx')) {
            Schema::table('appointments', fn (Blueprint $table) => $table->dropIndex('appointments_slot_status_idx'));
        }
        if (! $this->indexExists('appointments', 'unique_mechanic_slot')) {
            Schema::table('appointments', fn (Blueprint $table) => $table->unique(
                ['mechanic_id', 'appointment_date', 'appointment_time'],
                'unique_mechanic_slot'
            ));
        }

        foreach (['remember_token', 'avatar_path', 'onboarding_completed_at'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            foreach (DB::select("PRAGMA index_list('{$table}')") as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        return (bool) DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $index]
        );
    }
};
