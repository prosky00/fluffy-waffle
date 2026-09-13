<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faction_settings', function (Blueprint $table) {
            $table->unsignedInteger('duty_minutes_threshold')->nullable();
            $table->unsignedInteger('required_reports_count')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('faction_settings', function (Blueprint $table) {
            $table->dropColumn(['duty_minutes_threshold', 'required_reports_count']);
        });
    }
};
