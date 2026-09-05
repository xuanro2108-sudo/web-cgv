<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;


// Đăng ký khách hàng
Route::post(
    '/auth/register',
    [AuthController::class, 'register']
);


// Đăng nhập khách hàng
Route::post(
    '/auth/customer/login',
    [AuthController::class, 'loginCustomer']
);


// Đăng nhập nhân viên / quản lý
Route::post(
    '/auth/internal/login',
    [AuthController::class, 'loginInternal']
);


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