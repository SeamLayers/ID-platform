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
    CompanyController
};


Route::prefix('v1')->group( function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth', 'role:superadmin'])->group(function () {
        Route::middleware(['permission:company.view'])->group(function () {
            Route::apiResource('companies', CompanyController::class);
        });

    });


    Route::middleware(['auth', 'role:owner'])->group(function () {
        Route::middleware(['permission:company.view'])->group(function () {
            Route::get('/owner/companies', [CompanyController::class, 'index']);
        });
    });


    Route::middleware(['auth', 'role:employee'])->group(function () {
        Route::get('/employee', fn () => 'Employee panel');
    });



    Route::prefix('auth')->name('api.v1.auth.')->group(function () {
            Route::post('register', [RegisteredUserController::class, 'store'])->name('register');
            Route::post('login', [AuthenticatedSessionController::class, 'login'])->name('login');
            Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
            Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
            Route::post('send-otp', [RegisteredUserController::class, 'sendOtp'])->name('send-otp');
            Route::post('verify-otp', [RegisteredUserController::class, 'verifyOtp'])->name('verify-otp');


        // Authenticated routes
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('logout', [AuthenticatedSessionController::class, 'logout'])->name('logout');
        });
    });

    Route::get('privacy-policy', [SettingController::class, 'privacy']);
    Route::get('global-constants', [SettingController::class, 'GlobalConstants']);
    Route::get('terms-conditions', [SettingController::class, 'terms']);
    Route::get('contact-us', [SettingController::class, 'contact']);



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
