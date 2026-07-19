<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NewPasswordController extends Controller
{
    /**
     * Reset user password via OTP
     */
    public function store(ResetPasswordRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return ResponseHelper::error(
                __('messages.user_not_found'),
                null,
                404
            );
        }

        // The submitted OTP MUST match the one we mailed, and must still be
        // valid. Without this check any six digits reset any known account's
        // password — a full authentication bypass.
        if (
            empty($user->reset_otp)
            || ! hash_equals((string) $user->reset_otp, (string) $validated['otp'])
        ) {
            return ResponseHelper::error(
                __('messages.invalid_otp'),
                null,
                422
            );
        }

        // otp_expires_at has no datetime cast on the model, so parse defensively.
        if ($user->otp_expires_at && Carbon::parse($user->otp_expires_at)->isPast()) {
            return ResponseHelper::error(
                __('messages.otp_expired'),
                null,
                422
            );
        }

        $user->update([
            'password'            => Hash::make($validated['password']),
            'reset_otp'           => null,
            'otp_expires_at'      => null,
            'remember_token'      => Str::random(60),
            'must_reset_password' => false,
            // The temp-password clock is irrelevant once the user picks their
            // own password; leaving it set would 401 them on the next login.
            'expire_password'     => null,
        ]);

        // Never echo the user model back on an unauthenticated endpoint — it
        // carries reset_otp and other internals.
        return ResponseHelper::success(
            null,
            __('messages.password_reset_success')
        );
    }
}
