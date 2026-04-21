<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    /**
     * Resend the email verification link.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return ResponseHelper::success(
                null,
                __('messages.email_already_verified')
            );
        }

        $user->sendEmailVerificationNotification();

        return ResponseHelper::success(
            null,
            __('messages.verification_link_sent')
        );
    }
}
