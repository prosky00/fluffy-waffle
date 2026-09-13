<?php

namespace Database\Seeders;

use App\Models\FactionSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Only run explicitly (DEMO_SEED=true in entrypoint.sh) — creates a known-password
 * admin account, so this must never run against a real deployment's database.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        FactionSetting::singleton()->update(['name' => 'Demo Faction']);

        User::updateOrCreate(
            ['username' => 'demo'],
            [
                'name'         => 'Demo Admin',
                'in_game_name' => 'Demo Admin',
                'password'     => 'demo12345',
                'is_admin'     => true,
                'is_member'    => true,
            ]
        );
    }
}
