<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faction_settings', function (Blueprint $table) {
            $table->foreignId('hr_department_id')->nullable()->after('name')->constrained('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('faction_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hr_department_id');
        });
    }
};
