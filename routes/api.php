<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ADMIN API  (prefix: /api/admin)
|--------------------------------------------------------------------------
| Tomar purono admin login / logout route thakle sheta muche ei block ta dao.
| Website er onno API route gula (jodi thake) eitar upore / niche jemon ache rakho.
*/

Route::prefix('admin')->group(function () {

    // Login: 1 minute e 5 bar er beshi chesta korle 429 (RouteServiceProvider e 'admin-login')
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:admin-login');

    // Login kora + active user
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        // Users & Roles: shudhu "users" permission thakle
        Route::middleware('permission:users')->group(function () {
            Route::apiResource('users', UserController::class);
            Route::apiResource('roles', RoleController::class);
        });

        /* Pore notun admin API emon kore add koro:
        Route::middleware('permission:queries')->group(function () {
            Route::apiResource('queries', QueryController::class);
        });
        */
    });
});
