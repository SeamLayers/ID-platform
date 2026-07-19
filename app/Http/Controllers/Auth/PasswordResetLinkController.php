<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Mail\SendOtpMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PasswordResetLinkController extends Controller
{

    /**
     * Send OTP to user's email for password reset
     */
    public function store(Request $request): JsonResponse
    {
        // 1️⃣ Validate email
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                implode(', ', $validator->errors()->all()),
                $validator->errors(),
                422
            );
        }

        // 2️⃣ Fetch user
        $user = User::where('email', $request->email)->first();

        // 3️⃣ Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // 4️⃣ Save OTP and expiration
        $user->update([
            'reset_otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // 5️⃣ Send OTP email. A dead SMTP host must not 500 the endpoint — the
        // OTP is already stored, so the reset still completes once mail is back.
        try {
            Mail::to($user->email)->send(new SendOtpMail($otp));
        } catch (\Throwable $e) {
            Log::error('Reset OTP email failed', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        // Deliberately returns NOTHING. Echoing the user model exposed
        // data.reset_otp, which meant anyone who could name an email address
        // could read its reset code and take over the account (superadmin
        // included). The code is now email-only; the mobile app already treats
        // auto-fill as optional and falls back to manual entry.
        return ResponseHelper::success(
            null,
            __('messages.otp_sent')
        );
    }
}
