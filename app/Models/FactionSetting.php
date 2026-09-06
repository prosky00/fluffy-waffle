<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactionSetting extends Model
{
    protected $table = 'faction_settings';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'header_text', 'logo_url', 'favicon_url', 'events_content'];

    public static function singleton(): self
    {
        return self::firstOrCreate(['id' => 'singleton'], [
            'name'        => 'Faction',
            'header_text' => 'Dashboard',
        ]);
    }
}
