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
    EmployeeProjectController,
    EmployeeController,
    DepartmentController
};


Route::prefix('v1')->group( function () {

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
                });

                /*
                |-------------------------------
                | مشتركة (Superadmin + Owner)
                |-------------------------------
                */
                Route::middleware('role:superadmin|owner')->group(function () {
                    Route::apiResources([
                        'company-branch'   => CompanyBranchController::class,
                        'department'       => DepartmentController::class,
                        'employee'         => EmployeeController::class,
                        'project'          => ProjectController::class,
                        'employee-project' => EmployeeProjectController::class,
                    ]);
                    Route::post('register', [RegisteredUserController::class, 'store'])->name('register');

                });
            });

        /*
        |----------------------------------------
        | Mobile (Employee)
        |----------------------------------------
        */
        Route::prefix('mobile')
            ->middleware(['auth', 'role:employee'])
            ->group(function () {
                Route::get('/employee', fn () => 'Employee panel');
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
