<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class LegalContentSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'privacy_policy_en'],
            ['value' => '<h2>Privacy Policy</h2><p>Your real privacy policy content here.</p>']
        );

        Setting::updateOrCreate(
            ['key' => 'privacy_policy_ar'],
            ['value' => '<h2>سياسة الخصوصية</h2><p>محتوى سياسة الخصوصية باللغة العربية.</p>']
        );

        Setting::updateOrCreate(
            ['key' => 'terms_conditions_en'],
            ['value' => '<h2>Terms &amp; Conditions</h2><p>Your real terms and conditions here.</p>']
        );

        Setting::updateOrCreate(
            ['key' => 'terms_conditions_ar'],
            ['value' => '<h2>الشروط والأحكام</h2><p>محتوى الشروط والأحكام باللغة العربية.</p>']
        );
    }
}
