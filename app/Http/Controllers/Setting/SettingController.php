<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Helpers\SettingHelper;
use App\Models\Advertisement;
use App\Models\AdvertisementContracts;
use App\Models\OurWork;
use App\Models\Rating;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{



    /**
     * Get Privacy Policy
     */
    public function privacy(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'lang'            => 'required|in:ar,en'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json([
                'success' => false,
                'message' => implode(', ', $errors->all()),
                'errors'  => $errors->messages()
            ], 400);
        }
        $lang = $request->lang;
        return $this->getSetting("privacy_policy_{$lang}");
    }
    public function GlobalConstants()
    {
        $settings = Setting::where('key', 'like', 'constants_%')->get();

        return ResponseHelper::success($settings,'success retrieve data');
    }

    /**
     * Get Terms & Conditions
     */
    public function terms(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'lang'            => 'required|in:ar,en'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json([
                'success' => false,
                'message' => implode(', ', $errors->all()),
                'errors'  => $errors->messages()
            ], 400);
        }
        $lang = $request->lang;
        return $this->getSetting("terms_conditions_$lang");
    }

    /**
     * Get Contact Us
     */
    public function contact()
    {
        return ResponseHelper::success([
            'email'    => SettingHelper::get('contact_email'),
            'phone'    => SettingHelper::get('contact_phone'),
            'whatsapp' => SettingHelper::get('contact_whatsapp'),
            'address'  => SettingHelper::get('contact_address'),
        ]);
    }

    /**
     * Common function for text settings
     */
    private function getSetting(string $key)
    {
        $setting = Setting::where('key', $key)->first();


        return ResponseHelper::success([
            'content' => $setting?->value ?? ''
        ]);
    }



    public function AppStatistics()
    {
        $stats = [
            'users_count'        => User::count(),
            'services_count'     => Advertisement::count(),

            'contracts_count'    => AdvertisementContracts::count(),
            'completed_contracts'=> AdvertisementContracts::where('contract_status', 'completed')->count(),

            'total_earnings'     => AdvertisementContracts::where('contract_status', 'completed')->sum('actual_amount'),

            'works_count'        => OurWork::count(),

            'ratings_count'      => Rating::count(),
            'rating_avg'         => round(Rating::avg('rate'), 1),
        ];

        return ResponseHelper::success($stats,'app stats retrieved successfully');
    }
}
