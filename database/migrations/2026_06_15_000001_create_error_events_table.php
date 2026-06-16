<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('error_issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('environment')->nullable();
            $table->string('release')->nullable();
            $table->string('exception_class');
            $table->text('message');
            $table->longText('stack_trace');
            $table->string('url')->nullable();
            $table->string('request_method')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::table('error_events', function (Blueprint $table) {
            $table->index(['error_issue_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_events');
    }
};
