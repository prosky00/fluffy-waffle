<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unique(['department_id', 'user_id']);
            $table->timestamps();
        });

        // Migrate existing single department_id assignments into the pivot
        $rows = DB::table('users')
            ->whereNotNull('department_id')
            ->get(['id', 'department_id']);

        foreach ($rows as $row) {
            DB::table('department_user')->insertOrIgnore([
                'department_id' => $row->department_id,
                'user_id'       => $row->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('department_user');
    }
};
