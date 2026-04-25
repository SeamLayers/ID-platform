<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Requests\Auth\LoginRequest;
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


    /**
     * Login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'        => 'required|email',
            'password'     => 'required|string',
            'device_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                __('messages.validation_failed'),
                $validator->errors(),
                422
            );
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ResponseHelper::error(
                __('messages.invalid_credentials'),
                null,
                401
            );
        }

        // Update device token safely
        $user->update([
            'device_token' => $request->device_token
        ]);

        // Create API token
        $token = $user->createToken('api-token')->plainTextToken;
        $user->token = $token;
        $user->roles_name = $user->getRoleNames();
        $user->permissons = $user->getAllPermissions()->pluck('name');
        return ResponseHelper::success(new UserResource($user), __('messages.login_success'));
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
