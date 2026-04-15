<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminder_schedules', function (Blueprint $table) {
            $table->timestamp('last_reminder_failure_at')->nullable()->after('next_run_at');
            $table->string('last_reminder_failure_reason', 191)->nullable()->after('last_reminder_failure_at');
            $table->unsignedInteger('reminder_failure_count')->default(0)->after('last_reminder_failure_reason');
        });
    }

    public function down(): void
    {
        Schema::table('reminder_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'last_reminder_failure_at',
                'last_reminder_failure_reason',
                'reminder_failure_count',
            ]);
        });
    }
};
