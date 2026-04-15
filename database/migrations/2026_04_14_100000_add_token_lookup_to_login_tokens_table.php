<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_tokens', function (Blueprint $table) {
            $table->string('token_lookup', 64)->nullable()->after('purpose');
        });

        Schema::table('login_tokens', function (Blueprint $table) {
            $table->unique('token_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('login_tokens', function (Blueprint $table) {
            $table->dropUnique(['token_lookup']);
            $table->dropColumn('token_lookup');
        });
    }
};
