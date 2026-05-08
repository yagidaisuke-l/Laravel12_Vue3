<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RankController;
use App\Http\Controllers\Api\OptionController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\CodewordController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CastController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\DispatchController;
use App\Http\Controllers\Api\ContactController;

// 認証不要
Route::post('/login',   [AuthController::class,   'login']);
Route::post('/contact', [ContactController::class, 'send']);

// 認証必要
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    Route::apiResource('ranks',      RankController::class)->except('show');
    Route::apiResource('options',    OptionController::class)->except('show');
    Route::apiResource('areas',      AreaController::class)->except('show');
    Route::apiResource('codewords',  CodewordController::class)->except('show');

    Route::apiResource('customers',  CustomerController::class);
    Route::put('customers/{customer}/ng-casts', [CustomerController::class, 'syncNgCasts']);

    Route::apiResource('casts',      CastController::class);
    Route::put('casts/{cast}/ng-customers', [CastController::class, 'syncNgCustomers']);

    Route::apiResource('drivers',    DriverController::class)->except('show');

    Route::apiResource('shifts',     ShiftController::class)->except('show');
    Route::post('shifts/bulk',       [ShiftController::class, 'bulkStore']);
    Route::post('shifts/absent-from',[ShiftController::class, 'markAbsentFrom']);

    Route::apiResource('reservations', ReservationController::class);
    Route::apiResource('dispatches',   DispatchController::class)->except('show');
});
