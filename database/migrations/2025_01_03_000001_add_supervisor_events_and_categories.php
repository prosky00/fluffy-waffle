<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_supervisor to users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_supervisor')->default(false)->after('is_admin');
        });

        // Add events_content to faction_settings
        Schema::table('faction_settings', function (Blueprint $table) {
            $table->longText('events_content')->nullable()->after('favicon_url');
        });

        // Create report_categories table
        Schema::create('report_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 50)->unique();
            $table->string('color', 20)->default('#6b7280');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default categories
        DB::table('report_categories')->insert([
            ['name' => 'Járőr',    'slug' => 'PATROL',   'color' => '#3b82f6', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Incidens', 'slug' => 'INCIDENT',  'color' => '#ef4444', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Képzés',   'slug' => 'TRAINING',  'color' => '#10b981', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gyűlés',   'slug' => 'MEETING',   'color' => '#f59e0b', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Egyéb',    'slug' => 'OTHER',     'color' => '#6b7280', 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Change reports.category from enum to string so custom categories work in MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reports MODIFY COLUMN category VARCHAR(50) NOT NULL DEFAULT 'OTHER'");
        }
        // SQLite stores enum as text — any string value is already accepted
    }

    public function down(): void
    {
        Schema::dropIfExists('report_categories');

        Schema::table('faction_settings', function (Blueprint $table) {
            $table->dropColumn('events_content');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_supervisor');
        });
    }
};
