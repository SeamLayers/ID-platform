<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{
    RegisteredUserController,
    AuthenticatedSessionController,
    PasswordResetLinkController,
    NewPasswordController
};
use App\Http\Controllers\Setting\{
    NotificationController,
    SettingController
};
use \App\Http\Controllers\Dashboard\{
    CompanyController,
    CompanyBranchController,
    ProjectController,
    RolesController,
    EmployeeController,
    EmployeeProjectController,
    DepartmentController,
    BusinessCardController,
    BusinessCardTemplateController,
    OverviewController
};
use App\Http\Controllers\Public\PublicCardController;
use App\Http\Controllers\Mobile\MyCardController;
use App\Http\Controllers\Mobile\ReceivedContactController;


Route::prefix('v1')->group( function () {
    Route::get('/card/{slug}', [BusinessCardController::class, 'CardSlug']);
    // Bound on the 40-character public_url, NOT the primary key. Bound on the
    // id, this route let anyone walk 1, 2, 3… and collect a full contact card
    // for every published employee on the platform; the visibility gate in the
    // controller stops the unpublished ones leaking, but only an unguessable
    // handle stops the directory being enumerable at all. Same handle as every
    // other public surface.
    Route::get('/business-card/{card:public_url}/vcard', [BusinessCardController::class, 'downloadVCard'])
        ->name('business-card.vcard');

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->name('api.v1.auth.')->group(function () {
        Route::post('login', [AuthenticatedSessionController::class, 'login'])->name('login');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
        Route::post('send-otp', [RegisteredUserController::class, 'sendOtp'])->name('send-otp');
        Route::post('verify-otp', [RegisteredUserController::class, 'verifyOtp'])->name('verify-otp');


        // Authenticated routes
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('logout', [AuthenticatedSessionController::class, 'logout'])->name('logout');
            // "Me" endpoint — returns the current user (id/name/email/phone/
            // user_type/roles/permissions) for any authenticated role. The
            // mobile profile screen uses this to refresh on pull-to-refresh.
            Route::get('profile', [RegisteredUserController::class, 'profileData'])->name('profile');
            // Authenticated password change — used by the forced first-login
            // reset (temp password → own password) and the normal change action.
            Route::post('change-password', [RegisteredUserController::class, 'changePassword'])->name('change-password');
            // Refresh the stored FCM/web-push device token after login (web push
            // permission is granted asynchronously, usually after the login POST).
            Route::post('device-token', [AuthenticatedSessionController::class, 'updateDeviceToken'])->name('device-token');
        });
    });

    Route::name('api.v1.auth.')->group(function () {

        /*
        |----------------------------------------
        | Dashboard (Web / Admin)
        |----------------------------------------
        */
        Route::prefix('dashboard')
            ->middleware('auth:sanctum')
            ->group(function () {
                /*
                |-------------------------------
                | Super Admin Routes
                |-------------------------------
                */
                Route::middleware('role:superadmin')->group(function () {
                    Route::apiResource('company', CompanyController::class);

                });

                /*
                |-------------------------------
                | Owner Routes   - have account company in id plus
                |-------------------------------
                */
                Route::middleware('role:owner')->group(function () {
                    Route::get('owner/company', [CompanyController::class, 'show']);
                    // Owner self-service edit of their own company (tenancy-safe;
                    // resolves the company from the authed user, not a route id).
                    // POST (not PUT) so multipart logo uploads work under PHP.
                    Route::post('owner/company', [CompanyController::class, 'updateOwn']);
                });

                /*
                |-------------------------------
                | مشتركة (Superadmin + Owner)
                |-------------------------------
                */
                Route::middleware('role:superadmin|owner')->group(function () {
                    // Real dashboard analytics (replaces the old hardcoded
                    // charts on the web dashboard home). Tenancy-scoped inside
                    // the controller so an owner only sees their own numbers.
                    Route::get('overview', [OverviewController::class, 'overview']);
                    Route::apiResources([
                        'company-branch'   => CompanyBranchController::class,
                        'department'       => DepartmentController::class,
                        'employee'         => EmployeeController::class,
                        'project'          => ProjectController::class,
                        // Restored — dashboard's /assignments page needs
                        // this. Was accidentally dropped from the list
                        // during the business-cards additions.
                        'employee-project' => EmployeeProjectController::class,
                        'business-cards-templates'          => BusinessCardTemplateController::class,
                        'business-cards'          => BusinessCardController::class
                    ]);
                    Route::apiResource('roles', RolesController::class);
                    Route::post('roles/{role}/users', [RolesController::class, 'assignUsers']);
                    Route::post('register', [RegisteredUserController::class, 'store'])->name('register');
                    Route::post('business-cards/{id}/submit', [BusinessCardController::class, 'submit']);
                    // Owner's review verdict on an employee's submission: accept
                    // it (approve → publish) or send it back with a note.
                    Route::post('business-cards/{id}/approve', [BusinessCardController::class, 'approve']);
                    Route::post('business-cards/{id}/request-changes', [BusinessCardController::class, 'requestChanges']);
                    Route::post('business-cards/{id}/publish', [BusinessCardController::class, 'publish']);
                    Route::post('business-cards/{id}/deactivate', [BusinessCardController::class, 'deactivate']);
                    Route::post('business-cards/{id}/track', [BusinessCardController::class, 'track']);
                    Route::post('business-cards/{id}/analytics', [BusinessCardController::class, 'analytics']);
                });
            });

        /*
        |----------------------------------------
        | Mobile (Employee)
        |----------------------------------------
        */
        Route::prefix('mobile')
            ->middleware(['auth', 'role:superadmin|employee'])
            ->group(function () {
                // Read — reviewer queue. The dashboard list at
                // /dashboard/business-cards is gated to superadmin|owner,
                // so mobile employees with `business_card.view` need a
                // mobile-scoped read route.
                Route::get('business-cards', [BusinessCardController::class, 'index']);
                Route::get('business-cards/{id}', [BusinessCardController::class, 'show']);

                // Workflow
                Route::post('business-cards/{id}/approve', [BusinessCardController::class, 'approve']);
                Route::post('business-cards/{id}/reject', [BusinessCardController::class, 'reject']);

                /*
                 * The employee's OWN card — no id in the path, so it is always
                 * resolved from the authenticated user's employee record.
                 *
                 *   GET  my-card         → the card + its template design
                 *   POST my-card         → personalise (photo, colours, bio,
                 *                          second phone) — multipart
                 *   POST my-card/submit  → hand it to the owner for review
                 *   POST my-card/reopen  → take it back into draft: withdraw an
                 *                          unreviewed submission, or start a new
                 *                          version of an approved/live card
                 *                          (the published one stays up meanwhile)
                 */
                Route::get('my-card', [MyCardController::class, 'show']);
                Route::post('my-card', [MyCardController::class, 'update']);
                Route::post('my-card/submit', [MyCardController::class, 'submit']);
                Route::post('my-card/reopen', [MyCardController::class, 'reopen']);

                /*
                 * The employee's inbox of people who scanned their card and
                 * sent their own details back. Scoped to the authenticated
                 * user's employee row inside the controller — there is no
                 * id-addressable path into another employee's contacts.
                 */
                Route::get('received-contacts', [ReceivedContactController::class, 'index']);
                Route::get('received-contacts/unread-count', [ReceivedContactController::class, 'unreadCount']);
                Route::post('received-contacts/{id}/read', [ReceivedContactController::class, 'markAsRead']);
                Route::delete('received-contacts/{id}', [ReceivedContactController::class, 'destroy']);
            });

    });


    Route::get('privacy-policy', [SettingController::class, 'privacy']);
    Route::get('global-constants', [SettingController::class, 'GlobalConstants']);
    Route::get('terms-conditions', [SettingController::class, 'terms']);
    Route::get('contact-us', [SettingController::class, 'contact']);

    /*
    |--------------------------------------------------------------------------
    | Public Business Cards (no auth) — landing page consumes these.
    |
    |   GET  /cards/{public_url}        → render one published card
    |   POST /cards/{public_url}/track  → fire-and-forget interaction tracking
    |--------------------------------------------------------------------------
    */
    Route::get('cards/{public_url}', [PublicCardController::class, 'show']);
    Route::post('cards/{public_url}/track', [PublicCardController::class, 'track']);

    // Reverse contact exchange: a visitor with no app sends their OWN details
    // back to the card holder. Unauthenticated and therefore IP-throttled —
    // it is the only public route on the platform that writes personal data.
    Route::post('cards/{public_url}/contact', [PublicCardController::class, 'shareContact'])
        ->middleware('throttle:card-share');



    Route::middleware('auth:sanctum')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    // Cheap badge count, and a one-shot "clear the badge" for the app.
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
});

    // The `notifications-firebase` debug route used to live here: no auth, and
    // it fired a real push at a device token hardcoded in the source. Removed —
    // anyone who found the URL could spam that handset.

});
