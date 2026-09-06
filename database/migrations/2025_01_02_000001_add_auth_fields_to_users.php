<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('discord_id');
            $table->string('password')->nullable()->after('username');
            $table->boolean('is_suspended')->default(false)->after('is_admin');
            $table->string('discord_id')->nullable()->change();
        });

        // Auto-generate usernames for any existing users
        foreach (DB::table('users')->whereNull('username')->get(['id', 'name']) as $user) {
            $base     = strtolower(preg_replace('/\s+/', '.', preg_replace('/[^a-z0-9\s]/i', '', $user->name)));
            $base     = $base ?: 'felhasznalo';
            $username = $base;
            $n        = 1;
            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base . $n++;
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'password', 'is_suspended']);
        });
    }
};
