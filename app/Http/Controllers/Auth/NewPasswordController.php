<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Models\VerificationCode;
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

        $user->update([
            'password'            => Hash::make($validated['password']),
            'reset_otp'           => null,
            'otp_expires_at'      => null,
            'remember_token'      => Str::random(60),
            'must_reset_password' => false,
        ]);

        return ResponseHelper::success(
            $user,
            __('messages.password_reset_success')
        );
    }
}
