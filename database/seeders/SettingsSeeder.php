<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds the keys that the public settings endpoints expect.
 *
 *   * `privacy_policy_en|ar`   → bilingual privacy HTML
 *   * `terms_conditions_en|ar` → bilingual terms HTML
 *   * `contact_*`              → contact-us payload (email/phone/whatsapp/address)
 *   * `constants_*`            → app-wide constants surfaced to clients
 *
 * Run via `php artisan db:seed --class=SettingsSeeder` or include in
 * `DatabaseSeeder@run`.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'privacy_policy_en' => '<h2>Privacy Policy</h2><p>Placeholder privacy policy. Replace via the dashboard.</p>',
            'privacy_policy_ar' => '<h2>سياسة الخصوصية</h2><p>نص افتراضي. يرجى تحديثه من لوحة التحكم.</p>',

            'terms_conditions_en' => '<h2>Terms &amp; Conditions</h2><p>Placeholder terms. Replace via the dashboard.</p>',
            'terms_conditions_ar' => '<h2>الشروط والأحكام</h2><p>نص افتراضي. يرجى تحديثه من لوحة التحكم.</p>',

            'contact_email'    => 'support@idplus.cfd',
            'contact_phone'    => '+966500000000',
            'contact_whatsapp' => '+966500000000',
            'contact_address'  => 'Riyadh, Saudi Arabia',

            'constants_app_name'    => 'iD+ by Mhawer',
            'constants_app_version' => '1.0.0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
