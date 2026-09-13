<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faction_settings', function (Blueprint $table) {
            $table->string('discord_announcement_channel_id')->nullable();
            $table->string('discord_reports_channel_id')->nullable();
            $table->string('discord_applications_channel_id')->nullable();
            $table->string('discord_audit_channel_id')->nullable();
            $table->string('discord_member_role_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('faction_settings', function (Blueprint $table) {
            $table->dropColumn([
                'discord_announcement_channel_id',
                'discord_reports_channel_id',
                'discord_applications_channel_id',
                'discord_audit_channel_id',
                'discord_member_role_id',
            ]);
        });
    }
};
