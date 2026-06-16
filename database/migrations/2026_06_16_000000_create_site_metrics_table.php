<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_metrics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('server_id');
            $table->unsignedInteger('requests')->default(0);
            $table->float('avg_response_ms')->nullable();
            $table->float('p95_response_ms')->nullable();
            $table->float('error_rate')->nullable();
            $table->unsignedBigInteger('bytes')->default(0);
            $table->unsignedInteger('status_2xx')->default(0);
            $table->unsignedInteger('status_3xx')->default(0);
            $table->unsignedInteger('status_4xx')->default(0);
            $table->unsignedInteger('status_5xx')->default(0);
            $table->timestamp('window_start');
            $table->timestamps();

            $table->index(['site_id', 'window_start']);
            $table->index(['server_id', 'window_start']);
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_metrics');
    }
};
