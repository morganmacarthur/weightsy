<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_point_id')->nullable()->constrained()->nullOnDelete();
            $table->string('metric_type', 30);
            $table->decimal('value_decimal', 6, 2)->nullable();
            $table->unsignedSmallInteger('systolic')->nullable();
            $table->unsignedSmallInteger('diastolic')->nullable();
            $table->date('occurred_on');
            $table->timestamp('received_at')->nullable();
            $table->string('source_type', 30)->default('web_manual');
            $table->string('raw_input')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'occurred_on']);
            $table->index(['user_id', 'metric_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkins');
    }
};
