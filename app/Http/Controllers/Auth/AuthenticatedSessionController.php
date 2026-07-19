<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return ResponseHelper::error(
                __('messages.invalid_credentials'),
                null,
                401
            );
        }

        if ($user->expire_password && $user->expire_password < now()) {
            return ResponseHelper::error(
                __('messages.password_expired'),
                null,
                401
            );
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return ResponseHelper::error(
                __('messages.invalid_credentials'),
                null,
                401
            );
        }

        $user->update([
            'device_token'    => $validated['device_token'],
            'expire_password' => null,
            'is_login_active' => 1,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        $user->token = $token;
        $user->roles_name = $user->getRoleNames();
        $user->permissions = $user->getAllPermissions()->pluck('name');

        return ResponseHelper::success(
            new UserResource($user),
            __('messages.login_success')
        );
    }

    /**
     * Refresh the authenticated user's FCM / web-push device token.
     *
     * Browsers grant notification permission asynchronously (usually after the
     * login POST already stored a placeholder), so the dashboard calls this once
     * it has a real FCM token from getToken(). Mobile can use it on token refresh.
     */
    public function updateDeviceToken(Request $request)
    {
        $validated = $request->validate([
            'device_token' => ['required', 'string', 'max:500'],
        ]);

        $request->user()->update(['device_token' => $validated['device_token']]);

        return ResponseHelper::success(null, __('messages.data_updated'));
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ResponseHelper::success(
            null,
            __('messages.logout_success')
        );
    }
}
