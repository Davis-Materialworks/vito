<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint')->index();
            $table->string('exception_class');
            $table->string('normalized_message', 1024);
            $table->string('top_frame')->nullable();
            $table->string('status')->default('unresolved');
            $table->integer('count')->default(1);
            $table->integer('affected_users')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_issues');
    }
};
