<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_weekly_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->unsignedInteger('minutes')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'week_start']);
        });

        // duty_minutes was a flat, never-reset number — replaced by per-week entries above.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('duty_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('duty_minutes')->default(0);
        });
        Schema::dropIfExists('duty_weekly_entries');
    }
};
