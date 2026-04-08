<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_point_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 20);
            $table->string('channel', 20);
            $table->string('provider', 30)->default('self_hosted');
            $table->string('external_id')->nullable();
            $table->string('in_reply_to')->nullable();
            $table->string('subject')->nullable();
            $table->text('body_text')->nullable();
            $table->string('parsed_status', 20)->default('pending');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['direction', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
