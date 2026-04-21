<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class NewPasswordController extends Controller
{
    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    /**
     * Reset user password via OTP
     */
    public function store(Request $request)
    {
        // 1️⃣ Validate input
        $validator = Validator::make($request->all(), [
            'otp'      => ['required'],
            'email'    => ['required','email','exists:users,email'],
            'password' => ['required','confirmed', Password::defaults()],
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

        if (!$user) {
            return ResponseHelper::error(
                __('messages.user_not_found'),
                null,
                404
            );
        }

        // 3️⃣ Check OTP match
        if ($user->reset_otp !== $request->otp) {
            return ResponseHelper::error(
                __('messages.invalid_otp'),
                null,
                400
            );
        }

        // 4️⃣ Check OTP expiration
        if ($user->otp_expires_at < now()) {
            return ResponseHelper::error(
                __('messages.expired_otp'),
                null,
                400
            );
        }

        // 5️⃣ Reset password
        $user->update([
            'password'       => Hash::make($request->password),
            'reset_otp'      => null,
            'otp_expires_at' => null,
            'remember_token' => Str::random(60),
        ]);

        return ResponseHelper::success(
            $user,
            __('messages.password_reset_success')
        );
    }
}
