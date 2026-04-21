<?php

namespace App\Http\Helpers;

use App\Models\Setting;

class SettingHelper
{
    /**
     * Get setting value by key
     */
   public static function get(string $key)
{
    return cache()->remember("setting_{$key}", 3600, function() use ($key) {
        return Setting::where('key', $key)->value('value');
    });
}

}
