<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactionSetting extends Model
{
    protected $table = 'faction_settings';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name', 'header_text', 'logo_url', 'favicon_url', 'events_content', 'hr_department_id',
        'discord_announcement_channel_id', 'discord_reports_channel_id',
        'discord_applications_channel_id', 'discord_audit_channel_id', 'discord_member_role_id',
        'discord_admin_role_id',
    ];

    public static function singleton(): self
    {
        return self::firstOrCreate(['id' => 'singleton'], [
            'name'        => 'Faction',
            'header_text' => 'Dashboard',
        ]);
    }

    public function hrDepartment()
    {
        return $this->belongsTo(Department::class, 'hr_department_id');
    }
}
