<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faction_settings', function (Blueprint $table) {
            $table->string('id')->primary()->default('singleton');
            $table->string('name')->default('Faction');
            $table->string('header_text')->default('Dashboard');
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->timestamps();
        });

        DB::table('faction_settings')->insert([
            'id'          => 'singleton',
            'name'        => 'Faction',
            'header_text' => 'Dashboard',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('faction_settings');
    }
};
