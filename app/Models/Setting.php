<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic key/value settings store used by:
 *   * `privacy_policy_{lang}`         → privacy policy HTML per locale
 *   * `terms_conditions_{lang}`       → terms HTML per locale
 *   * `contact_email|phone|whatsapp|address` → public contact info
 *   * `constants_*`                   → app-wide constants exposed to clients
 *
 * Read via [SettingHelper::get()] (cached 1h).
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    public $timestamps = true;
}
