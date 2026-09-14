<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BanVeTaiQuayController;
use App\Http\Controllers\Api\ComboSanPhamController;
use App\Http\Controllers\Api\DonHangComboController;
use App\Http\Controllers\Api\DonHangController;
use App\Http\Controllers\Api\DonHangKhuyenMaiController;
use App\Http\Controllers\Api\GheController;
use App\Http\Controllers\Api\HoSoKhachHangController;
use App\Http\Controllers\Api\HuyDonController;
use App\Http\Controllers\Api\KhuyenMaiController;
use App\Http\Controllers\Api\LichChieuController;
use App\Http\Controllers\Api\NhanVienController;
use App\Http\Controllers\Api\PhimController;
use App\Http\Controllers\Api\PhongChieuController;
use App\Http\Controllers\Api\QuanLyKhachHangController;
use App\Http\Controllers\Api\SanPhamController;
use App\Http\Controllers\Api\SoDoGheController;
use App\Http\Controllers\Api\ThanhToanController;
use App\Http\Controllers\Api\ThongKeController;
use App\Http\Controllers\Api\VeGheController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WEBHOOK / CẤU HÌNH THANH TOÁN
|--------------------------------------------------------------------------
*/

Route::post('webhooks/sepay', [ThanhToanController::class, 'webhook'])
    ->middleware('throttle:120,1');

Route::get('thanh-toan/config', function () {
    return response()->json([
        'data' => [
            'bank' => config('payments.sepay.bank', 'TPBank'),
            'accountNumber' => config(
                'payments.sepay.account_number',
                '21082005555'
            ),
            'accountName' => config(
                'payments.sepay.account_name',
                'TRAN THANH XUAN'
            ),
            'template' => config(
                'payments.sepay.template',
                'compact2'
            ),
        ],
    ]);
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

// Đăng ký khách hàng
Route::post('/auth/register', [
    AuthController::class,
    'register',
]);

// Đăng nhập khách hàng
Route::post('/auth/customer/login', [
    AuthController::class,
    'loginCustomer',
])->middleware('throttle:5,1');

// Đăng nhập nhân viên / quản lý
Route::post('/auth/internal/login', [
    AuthController::class,
    'loginInternal',
])->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| API TEST
|--------------------------------------------------------------------------
*/

Route::get('/test', function () {
    return response()->json([
        'message' => 'API CGV hoạt động!',
    ]);
});

/*
|--------------------------------------------------------------------------
| NỘI DUNG CÔNG KHAI
|--------------------------------------------------------------------------
*/

// Sản phẩm
Route::apiResource('san-phams', SanPhamController::class)
    ->only(['index', 'show'])
    ->parameters([
        'san-phams' => 'maSP',
    ]);

// Khuyến mãi
Route::apiResource('khuyen-mais', KhuyenMaiController::class)
    ->only(['index', 'show'])
    ->parameters([
        'khuyen-mais' => 'maKM',
    ]);

// Phim
Route::apiResource('phims', PhimController::class)
    ->only(['index', 'show'])
    ->parameters([
        'phims' => 'maPhim',
    ]);

// Lịch chiếu công khai cho khách hàng
Route::apiResource('lich-chieus', LichChieuController::class)
    ->only(['index', 'show'])
    ->parameters([
        'lich-chieus' => 'maLichChieu',
    ]);

// Phòng chiếu
Route::apiResource('phong-chieus', PhongChieuController::class)
    ->only(['index', 'show'])
    ->parameters([
        'phong-chieus' => 'maPhong',
    ]);

// Sơ đồ ghế
Route::apiResource('so-do-ghes', SoDoGheController::class)
    ->only(['index', 'show'])
    ->parameters([
        'so-do-ghes' => 'maSoDo',
    ]);

// Ghế
Route::apiResource('ghes', GheController::class)
    ->only(['index', 'show'])
    ->parameters([
        'ghes' => 'maGhe',
    ]);

// Combo
Route::apiResource('combos', ComboSanPhamController::class)
    ->only(['index', 'show'])
    ->parameters([
        'combos' => 'maCombo',
    ]);

/*
|--------------------------------------------------------------------------
| API YÊU CẦU ĐĂNG NHẬP
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */

    Route::get('/auth/me', [
        AuthController::class,
        'me',
    ]);

    Route::post('/auth/logout', [
        AuthController::class,
        'logout',
    ]);

    /*
    |--------------------------------------------------------------------------
    | HỒ SƠ KHÁCH HÀNG
    |--------------------------------------------------------------------------
    */

    Route::get('ho-so', [
        HoSoKhachHangController::class,
        'show',
    ]);

    Route::patch('ho-so', [
        HoSoKhachHangController::class,
        'update',
    ]);

    Route::patch('ho-so/mat-khau', [
        HoSoKhachHangController::class,
        'password',
    ])->middleware('throttle:5,1');

    /*
    |--------------------------------------------------------------------------
    | ĐƠN HÀNG
    |--------------------------------------------------------------------------
    */

    Route::apiResource('don-hangs', DonHangController::class)
        ->only(['index', 'store', 'show'])
        ->parameters([
            'don-hangs' => 'maDonHang',
        ]);

    Route::patch(
        'don-hangs/{maDonHang}/huy',
        HuyDonController::class
    );

    /*
    |--------------------------------------------------------------------------
    | VÉ GHẾ
    |--------------------------------------------------------------------------
    */

    Route::apiResource('ve-ghes', VeGheController::class)
        ->only(['index', 'show', 'store', 'update'])
        ->parameters([
            've-ghes' => 'maVe',
        ]);
Route::get('my-tickets', [
    VeGheController::class,
    'myTickets',
]);
Route::get('my-orders', [
    DonHangController::class,
    'myOrders',
]);
    /*
    |--------------------------------------------------------------------------
    | COMBO TRONG ĐƠN HÀNG
    |--------------------------------------------------------------------------
    */

    Route::post('don-hangs/{maDonHang}/combos', [
        DonHangComboController::class,
        'store',
    ]);

    Route::patch(
        'don-hangs/{maDonHang}/combos/{maCombo}',
        [
            DonHangComboController::class,
            'update',
        ]
    );

    Route::delete(
        'don-hangs/{maDonHang}/combos/{maCombo}',
        [
            DonHangComboController::class,
            'destroy',
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | KHUYẾN MÃI ĐƠN HÀNG
    |--------------------------------------------------------------------------
    */

    Route::post(
        'don-hangs/{maDonHang}/khuyen-mai/kiem-tra',
        [
            DonHangKhuyenMaiController::class,
            'check',
        ]
    );

    Route::post(
        'don-hangs/{maDonHang}/khuyen-mai',
        [
            DonHangKhuyenMaiController::class,
            'store',
        ]
    );

    Route::delete(
        'don-hangs/{maDonHang}/khuyen-mai',
        [
            DonHangKhuyenMaiController::class,
            'destroy',
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | THANH TOÁN
    |--------------------------------------------------------------------------
    */

    Route::post(
        'don-hangs/{maDonHang}/thanh-toan',
        [
            ThanhToanController::class,
            'store',
        ]
    );

    Route::get(
        'don-hangs/{maDonHang}/thanh-toan',
        [
            ThanhToanController::class,
            'show',
        ]
    );

    Route::post(
        'thanh-toans/{maTT}/xac-nhan',
        [
            ThanhToanController::class,
            'confirm',
        ]
    )->middleware('throttle:20,1');

    Route::post(
        'thanh-toans/{maTT}/gia-lap',
        [
            ThanhToanController::class,
            'simulate',
        ]
    )->middleware('throttle:20,1');

    /*
    |--------------------------------------------------------------------------
    | QUẢN LÝ COMBO / KHÁCH HÀNG / SẢN PHẨM / ĐƠN HÀNG
    |--------------------------------------------------------------------------
    */

    Route::get('quan-ly/combos', [
        ComboSanPhamController::class,
        'management',
    ]);

    Route::post('quan-ly/ban-ve-tai-quay', [
        BanVeTaiQuayController::class,
        'store',
    ]);

    Route::get('quan-ly/khach-hangs', [
        QuanLyKhachHangController::class,
        'index',
    ]);

    Route::get('quan-ly/khuyen-mais', [
        KhuyenMaiController::class,
        'management',
    ]);

    Route::patch(
        'quan-ly/khach-hangs/{maKH}/trang-thai',
        [
            QuanLyKhachHangController::class,
            'status',
        ]
    );

    Route::get(
        'quan-ly/khach-hangs/{maKH}/giao-dich',
        [
            QuanLyKhachHangController::class,
            'history',
        ]
    );

    Route::delete(
        'quan-ly/khach-hangs/{maKH}',
        [
            QuanLyKhachHangController::class,
            'destroy',
        ]
    );

    Route::get('quan-ly/san-phams', [
        SanPhamController::class,
        'management',
    ]);

    Route::get('quan-ly/don-hangs', [
        DonHangController::class,
        'management',
    ]);

    Route::get(
        'quan-ly/don-hangs/{maDonHang}',
        [
            DonHangController::class,
            'managementShow',
        ]
    );

    Route::post(
        'quan-ly/don-hangs/xem-qr',
        [
            DonHangController::class,
            'previewScan',
        ]
    );

    Route::post(
        'quan-ly/don-hangs/quet-ma',
        [
            DonHangController::class,
            'scan',
        ]
    );

    Route::post(
        'quan-ly/don-hangs/{maDonHang}/quet-ma',
        [
            DonHangController::class,
            'scan',
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | CRUD NỘI DUNG ĐÃ ĐĂNG NHẬP
    |--------------------------------------------------------------------------
    */

    Route::apiResource('san-phams', SanPhamController::class)
        ->only(['store', 'update', 'destroy'])
        ->parameters([
            'san-phams' => 'maSP',
        ]);

    Route::apiResource('khuyen-mais', KhuyenMaiController::class)
        ->only(['store', 'update', 'destroy'])
        ->parameters([
            'khuyen-mais' => 'maKM',
        ]);

    Route::apiResource('combos', ComboSanPhamController::class)
        ->only(['store', 'update', 'destroy'])
        ->parameters([
            'combos' => 'maCombo',
        ]);

    Route::apiResource('phims', PhimController::class)
        ->only(['store', 'update', 'destroy'])
        ->parameters([
            'phims' => 'maPhim',
        ]);

    /*
    |--------------------------------------------------------------------------
    | QUẢN LÝ LỊCH CHIẾU - CHỈ QUẢN LÝ
    |--------------------------------------------------------------------------
    */

    Route::apiResource('lich-chieus', LichChieuController::class)
        ->middleware('role:QUAN_LY')
        ->only(['store', 'update', 'destroy'])
        ->parameters([
            'lich-chieus' => 'maLichChieu',
        ]);

    /*
    |--------------------------------------------------------------------------
    | QUẢN LÝ PHÒNG CHIẾU - CHỈ QUẢN LÝ
    |--------------------------------------------------------------------------
    */

    Route::apiResource('phong-chieus', PhongChieuController::class)
        ->middleware('role:QUAN_LY')
        ->only(['update'])
        ->parameters([
            'phong-chieus' => 'maPhong',
        ]);

    /*
    |--------------------------------------------------------------------------
    | QUẢN LÝ GHẾ - CHỈ QUẢN LÝ
    | Khóa / mở lại ghế hỏng theo nghiệp vụ trong Word
    |--------------------------------------------------------------------------
    */

    Route::apiResource('ghes', GheController::class)
        ->middleware('role:QUAN_LY')
        ->only(['update'])
        ->parameters([
            'ghes' => 'maGhe',
        ]);
});

/*
|--------------------------------------------------------------------------
| QUẢN LÝ - CHỈ ROLE QUAN_LY
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'role:QUAN_LY',
])
    ->prefix('quan-ly')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | NHÂN VIÊN
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'nhan-viens',
            NhanVienController::class
        )->parameters([
            'nhan-viens' => 'maNV',
        ]);

        /*
        |--------------------------------------------------------------------------
        | THỐNG KÊ
        |--------------------------------------------------------------------------
        */

        Route::get(
            'thong-ke/danh-sach-phim',
            [
                ThongKeController::class,
                'danhSachPhim',
            ]
        );

        Route::get(
            'thong-ke/danh-sach-khuyen-mai',
            [
                ThongKeController::class,
                'danhSachKhuyenMai',
            ]
        );

        Route::get(
            'thong-ke/doanh-thu-ve',
            [
                ThongKeController::class,
                'doanhThuVe',
            ]
        );

        Route::get(
            'thong-ke/doanh-thu-combo',
            [
                ThongKeController::class,
                'doanhThuCombo',
            ]
        );

        Route::get(
            'thong-ke/theo-phim',
            [
                ThongKeController::class,
                'theoPhim',
            ]
        );

        Route::get(
            'thong-ke/khuyen-mai',
            [
                ThongKeController::class,
                'khuyenMai',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | DANH SÁCH LỊCH CHIẾU DÀNH CHO QUẢN LÝ
        |--------------------------------------------------------------------------
        */

        Route::get(
            'lich-chieus',
            [
                LichChieuController::class,
                'management',
            ]
        );
    });
