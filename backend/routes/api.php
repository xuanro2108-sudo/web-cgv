
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComboSanPhamController;
use App\Http\Controllers\Api\DonHangComboController;
use App\Http\Controllers\Api\DonHangController;
use App\Http\Controllers\Api\DonHangKhuyenMaiController;
use App\Http\Controllers\Api\GheController;
use App\Http\Controllers\Api\HuyDonController;
use App\Http\Controllers\Api\KhuyenMaiController;
use App\Http\Controllers\Api\LichChieuController;
use App\Http\Controllers\Api\PhimController;
use App\Http\Controllers\Api\PhongChieuController;
use App\Http\Controllers\Api\SoDoGheController;
use App\Http\Controllers\Api\ThanhToanController;
use App\Http\Controllers\Api\VeGheController;
use Illuminate\Support\Facades\Route;

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
    Route::post('don-hangs/{maDonHang}/thanh-toan', [ThanhToanController::class, 'store']);
    Route::get('don-hangs/{maDonHang}/thanh-toan', [ThanhToanController::class, 'show']);
    Route::post('thanh-toans/{maTT}/xac-nhan', [ThanhToanController::class, 'confirm'])->middleware('throttle:20,1');
    Route::post('thanh-toans/{maTT}/gia-lap', [ThanhToanController::class, 'simulate'])->middleware('throttle:20,1');
    Route::post('don-hangs/{maDonHang}/khuyen-mai/kiem-tra', [DonHangKhuyenMaiController::class, 'check']);
    Route::post('don-hangs/{maDonHang}/khuyen-mai', [DonHangKhuyenMaiController::class, 'store']);
    Route::delete('don-hangs/{maDonHang}/khuyen-mai', [DonHangKhuyenMaiController::class, 'destroy']);
    Route::apiResource('khuyen-mais', KhuyenMaiController::class)->only(['store', 'update', 'destroy'])->parameters(['khuyen-mais' => 'maKM']);
    Route::patch('don-hangs/{maDonHang}/huy', HuyDonController::class);
    Route::post('don-hangs/{maDonHang}/combos', [DonHangComboController::class, 'store']);
    Route::patch('don-hangs/{maDonHang}/combos/{maCombo}', [DonHangComboController::class, 'update']);
    Route::delete('don-hangs/{maDonHang}/combos/{maCombo}', [DonHangComboController::class, 'destroy']);

    Route::get(
        '/auth/me',
        [AuthController::class, 'me']
    );

    Route::post(
        '/auth/logout',
        [AuthController::class, 'logout']
    );
    Route::apiResource('don-hangs', DonHangController::class)
        ->only(['index', 'store', 'show'])
        ->parameters(['don-hangs' => 'maDonHang']);

    Route::apiResource('combos', ComboSanPhamController::class)
        ->only(['store', 'update', 'destroy'])
        ->parameters(['combos' => 'maCombo']);
});

// ==================== PHIM ====================
Route::apiResource('khuyen-mais', KhuyenMaiController::class)->only(['index', 'show'])->parameters(['khuyen-mais' => 'maKM']);

// API test
Route::get('/test', function () {
    return response()->json([
        'message' => 'API CGV hoạt động!',
    ]);
});

// CRUD phim
Route::apiResource('phims', PhimController::class)
    ->parameters(['phims' => 'maPhim']);
Route::apiResource('lich-chieus', LichChieuController::class)
    ->parameters(['lich-chieus' => 'maLichChieu']);
Route::apiResource('phong-chieus', PhongChieuController::class)
    ->only(['index', 'show', 'update'])
    ->parameters(['phong-chieus' => 'maPhong']);
Route::apiResource('so-do-ghes', SoDoGheController::class)
    ->only(['index', 'show'])
    ->parameters(['so-do-ghes' => 'maSoDo']);
Route::apiResource('ghes', GheController::class)
    ->only(['index', 'show', 'update'])
    ->parameters(['ghes' => 'maGhe']);
Route::apiResource('ve-ghes', VeGheController::class)
    ->middleware('auth:sanctum')
    ->only(['index', 'show', 'store', 'update'])
    ->parameters(['ve-ghes' => 'maVe']);

Route::apiResource('combos', ComboSanPhamController::class)
    ->only(['index', 'show'])
    ->parameters(['combos' => 'maCombo']);
