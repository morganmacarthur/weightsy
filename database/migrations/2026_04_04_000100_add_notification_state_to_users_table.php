<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('notification_confirmed_at')->nullable()->after('onboarding_completed_at');
            $table->timestamp('unsubscribed_at')->nullable()->after('notification_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notification_confirmed_at', 'unsubscribed_at']);
        });
    }
};
