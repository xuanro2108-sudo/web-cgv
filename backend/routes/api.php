
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PhimController;

// ==================== AUTHENTICATION ====================

// Đăng ký khách hàng
Route::post(
    '/auth/register',
    [AuthController::class, 'register']
);

// Đăng nhập khách hàng
Route::post(
    '/auth/customer/login',
    [AuthController::class, 'loginCustomer']
)->middleware('throttle:5,1');

// Đăng nhập nhân viên / quản lý
Route::post(
    '/auth/internal/login',
    [AuthController::class, 'loginInternal']
)->middleware('throttle:5,1');

// API yêu cầu đã đăng nhập
Route::middleware('auth:sanctum')->group(function () {

    Route::get(
        '/auth/me',
        [AuthController::class, 'me']
    );

    Route::post(
        '/auth/logout',
        [AuthController::class, 'logout']
    );
});

// ==================== PHIM ====================

// API test
Route::get('/test', function () {
    return response()->json([
        'message' => 'API CGV hoạt động!'
    ]);
});

// CRUD phim
Route::apiResource('phims', PhimController::class)
    ->parameters(['phims' => 'maPhim']);