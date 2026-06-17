<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_metrics', function (Blueprint $table) {
            $table->string('release')->nullable()->after('p95_response_ms');
        });
    }

    public function down(): void
    {
        Schema::table('site_metrics', function (Blueprint $table) {
            $table->dropColumn('release');
        });
    }
};
