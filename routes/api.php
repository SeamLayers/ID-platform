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


Route::prefix('v1')->group( function () {
    Route::get('/card/{slug}', [BusinessCardController::class, 'CardSlug']);
    Route::get('/business-card/{card}/vcard', [BusinessCardController::class, 'downloadVCard'])
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



    Route::middleware('auth:sanctum')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});


    // sent notification

    Route::get('notifications-firebase',function (){
        $firebase = new \App\Services\FirebaseService();
        $firebase->sendToDevice('eXqn4RqqTYeieigFQDAT9n:APA91bHK13e3JFTAouXNdbULg46oBGHScD7VZnsKKKv_FGSXhUO2sP2p0KhHOsa6FOUl7GFNucgJXVvV4tUsuhUtIu_E6TSektlAejZ22HH9_Jlqa0580dg',
            "Hi Semmo Basel ",
            'new order',[]
        );
    });


});
