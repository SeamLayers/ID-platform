<?php

namespace App\Http\Controllers\Auth;

use App\Http\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\VerificationCode;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\UpdateProfileRequest;
class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    /**
     * Register a new user
     */


    public function store(StoreUserRequest $request): JsonResponse
    {
        try {

            $validated = $request->validated();

            DB::beginTransaction();

            $plainPassword = Str::password(8);

            $user = User::create([

                'name' => $validated['name'],

                'email' => $validated['email'],

                'password' => Hash::make($plainPassword),

                'ip_address' => $request->ip(),

                'user_type' => $validated['user_type'],

                'phone' => $validated['phone'],

                'expire_password' => now()->addHours(48),

            ]);

            $user->assignRole($validated['user_type']);

            $token = $user->createToken('api-token')->plainTextToken;

            event(new Registered($user));

            $message = null;

            if ($validated['user_type'] === User::TYPE_EMPLOYEE) {

                $message = "Your account has been created successfully.

Email: {$validated['email']}
Temporary Password: {$plainPassword}

Please log in to the application and change your password within 48 hours.

Download ID Plus App:
Google Play / Apple Store";

                try {

//                SmsService::sendSMS(
//                    $validated['phone'],
//                    $message
//                );

                } catch (\Throwable $smsException) {

                    Log::error('SMS Sending Failed', [
                        'phone' => $validated['phone'],
                        'error' => $smsException->getMessage()
                    ]);
                }
            }

            DB::commit();

            return ResponseHelper::success([

                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'phone' => $user->phone,
                ],

                'token' => $token,

                'out_message' => $message

            ], __('messages.register_success'));

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('User Registration Failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return ResponseHelper::error(
                $e->getMessage()
            );
        }
    }

    /**
     * Send OTP to phone
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $countryCode = $validated['country_code'];

        $phoneNumber = $validated['phone'];

        $existingOtp = VerificationCode::where('phone_number', $phoneNumber)
            ->where('country_code', $countryCode)
            ->where('is_used', false)
            ->where('expiration_date', '>', now())
            ->latest()
            ->first();

        if ($existingOtp) {

            $remaining = $existingOtp->expiration_date->diffInSeconds(now());

            return ResponseHelper::error(
                __('messages.otp_wait', ['seconds' => $remaining]),
                429
            );
        }

        $otp = rand(1000, 9999);

        VerificationCode::create([
            'phone_number' => $phoneNumber,
            'country_code' => $countryCode,
            'code' => $otp,
            'expiration_date' => now()->addMinutes(5),
            'is_used' => false,
        ]);

        // SmsService::send($countryCode.$phoneNumber, $otp);

        return ResponseHelper::success([
            'phone' => "{$countryCode}{$phoneNumber}",
            'otp' => $otp
        ], __('messages.otp_sent'));
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $countryCode = $validated['country_code'];

        $phoneNumber = $validated['phone'];

        $record = VerificationCode::where('phone_number', $phoneNumber)
            ->where('country_code', $countryCode)
            ->where('code', $validated['otp'])
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$record) {

            return ResponseHelper::error(
                __('messages.invalid_otp'),
                422
            );
        }

        if ($record->expiration_date->isPast()) {

            return ResponseHelper::error(
                __('messages.otp_expired'),
                422
            );
        }
        $record->update([
            'is_used' => true
        ]);

        return ResponseHelper::success(
            null,
            __('messages.otp_verified')
        );
    }

    /**
     * Get current authenticated user profile
     */
    public function profileData(): JsonResponse
    {
        return ResponseHelper::success(
            auth()->user(),
            __('messages.profile_retrieved')
        );
    }

    /**
     * Update current user profile data
     */
    public function updateData(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {

            return ResponseHelper::error(
                __('messages.user_not_found'),
                404
            );
        }

        $blockedFields = [
            'email',
            'phone',
            'password',
            'password_confirmation'
        ];

        $user->update(
            $request->except($blockedFields)
        );

        return ResponseHelper::success(
            $user->fresh(),
            __('messages.profile_updated')
        );
    }

}
