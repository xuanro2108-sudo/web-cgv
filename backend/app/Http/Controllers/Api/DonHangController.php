<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DonHangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $taiKhoan = $request->user();

        if (
            $taiKhoan->vaiTro !== 'KHACH_HANG' ||
            $taiKhoan->trangThai !== 'HOAT_DONG'
        ) {
            return response()->json([
                'message' => 'Bạn không có quyền xem đơn hàng.',
            ], 403);
        }

        $khachHang = $taiKhoan->khachHang;

        if (
            !$khachHang ||
            $khachHang->trangThai !== 'HOAT_DONG'
        ) {
            return response()->json([
                'message' => 'Thông tin khách hàng không hợp lệ hoặc đã bị khóa.',
            ], 403);
        }

        $donHangs = DonHang::where('maKH', $khachHang->maKH)
            ->orderByDesc('ngayDat')
            ->orderByDesc('maDonHang')
            ->paginate(10);

        return response()->json($donHangs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Lấy tài khoản từ token đăng nhập
        $taiKhoan = $request->user();

        // 2. Chỉ cho phép khách hàng đang hoạt động tạo đơn online
        if (
            $taiKhoan->vaiTro !== 'KHACH_HANG' ||
            $taiKhoan->trangThai !== 'HOAT_DONG'
        ) {
            return response()->json([
                'message' => 'Bạn không có quyền tạo đơn hàng.',
            ], 403);
        }

        // 3. Lấy hồ sơ khách hàng liên kết với tài khoản
        $khachHang = $taiKhoan->khachHang;

        if (
            !$khachHang ||
            $khachHang->trangThai !== 'HOAT_DONG'
        ) {
            return response()->json([
                'message' => 'Thông tin khách hàng không hợp lệ hoặc đã bị khóa.',
            ], 403);
        }

        // 4. Tạo đơn hàng ban đầu, chưa có ghế hoặc combo
        $donHang = DonHang::create([
            'maDonHang' => 'DH' . Str::ulid(),
            'maKH' => $khachHang->maKH,
            'maNV' => null,
            'maKM' => null,
            'kieuDat' => 'ONLINE',
            'ngayDat' => now(),
            'tongTien' => 0,
            'maQR' => null,
            'trangThai' => 'CHO_THANH_TOAN',
        ]);

        // 5. Trả đơn vừa tạo cho frontend
        return response()->json([
            'message' => 'Tạo đơn hàng thành công.',
            'data' => $donHang,
        ], 201);
    }
    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $maDonHang)
    {
        $taiKhoan = $request->user();

        if (
            $taiKhoan->vaiTro !== 'KHACH_HANG' ||
            $taiKhoan->trangThai !== 'HOAT_DONG'
        ) {
            return response()->json([
                'message' => 'Bạn không có quyền xem đơn hàng.',
            ], 403);
        }

        $khachHang = $taiKhoan->khachHang;

        if (
            !$khachHang ||
            $khachHang->trangThai !== 'HOAT_DONG'
        ) {
            return response()->json([
                'message' => 'Thông tin khách hàng không hợp lệ hoặc đã bị khóa.',
            ], 403);
        }

        $donHang = DonHang::with([
            'veGhes.ghe',
            'veGhes.lichChieu.phim',
            'veGhes.lichChieu.phongChieu',
            'chiTietComboDonHangs',
            'khuyenMai',
            'thanhToan',
        ])
            ->where('maKH', $khachHang->maKH)
            ->where('maDonHang', $maDonHang)
            ->firstOrFail();

        return response()->json([
            'message' => 'Lấy chi tiết đơn hàng thành công.',
            'data' => $donHang,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
