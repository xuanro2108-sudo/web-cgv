<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KhachHang;
use App\Models\TaiKhoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // =========================
    // ĐĂNG KÝ KHÁCH HÀNG
    // =========================
    public function register(Request $request)
    {
        $data = $request->validate([
            'hoTen' => 'required|string|max:255',

            'soDienThoai' =>
                'required|string|max:20|unique:khach_hangs,soDienThoai',

            'email' =>
                'required|email|max:255|unique:khach_hangs,email',

            'ngaySinh' =>
                'nullable|date|before_or_equal:today',

            'gioiTinh' =>
                'nullable|in:NAM,NU',

            'tenDangNhap' =>
                'required|string|max:100|unique:tai_khoans,tenDangNhap',

            'matKhau' =>
                'required|string|min:6|confirmed',
        ]);

        $result = DB::transaction(function () use ($data) {

            // Sinh mã khách hàng
            $lastKhachHang = KhachHang::orderBy(
                'maKH',
                'desc'
            )->first();

            $nextKH = $lastKhachHang
                ? ((int) substr($lastKhachHang->maKH, 2)) + 1
                : 1;

            $maKH = 'KH' . str_pad(
                $nextKH,
                3,
                '0',
                STR_PAD_LEFT
            );

            // Sinh mã tài khoản
            $lastTaiKhoan = TaiKhoan::orderBy(
                'maTK',
                'desc'
            )->first();

            $nextTK = $lastTaiKhoan
                ? ((int) substr($lastTaiKhoan->maTK, 2)) + 1
                : 1;

            $maTK = 'TK' . str_pad(
                $nextTK,
                3,
                '0',
                STR_PAD_LEFT
            );

            // Tạo khách hàng
            $khachHang = KhachHang::create([
                'maKH' => $maKH,
                'hoTen' => $data['hoTen'],
                'soDienThoai' => $data['soDienThoai'],
                'email' => $data['email'],
                'ngaySinh' => $data['ngaySinh'] ?? null,
                'gioiTinh' => $data['gioiTinh'] ?? null,
                'ngayDangKy' => now()->toDateString(),
                'trangThai' => 'HOAT_DONG',
            ]);

            // Tạo tài khoản khách hàng
            $taiKhoan = TaiKhoan::create([
                'maTK' => $maTK,
                'tenDangNhap' => $data['tenDangNhap'],

                'matKhau' => Hash::make(
                    $data['matKhau']
                ),

                'vaiTro' => 'KHACH_HANG',
                'maKH' => $maKH,
                'maNV' => null,
                'trangThai' => 'HOAT_DONG',
            ]);

            return [
                'khachHang' => $khachHang,
                'taiKhoan' => $taiKhoan,
            ];
        });

        return response()->json([
            'message' => 'Đăng ký thành công',
            'khachHang' => $result['khachHang'],
            'taiKhoan' => $result['taiKhoan'],
        ], 201);
    }


    // =========================
    // LOGIN KHÁCH HÀNG
    // =========================
    public function loginCustomer(Request $request)
    {
        return $this->login(
            $request,
            ['KHACH_HANG'],
            'customer'
        );
    }


    // =========================
    // LOGIN NHÂN VIÊN / QUẢN LÝ
    // =========================
    public function loginInternal(Request $request)
    {
        return $this->login(
            $request,
            ['NHAN_VIEN', 'QUAN_LY'],
            'dashboard'
        );
    }


    // =========================
    // XỬ LÝ LOGIN CHUNG
    // =========================
    private function login(
        Request $request,
        array $roles,
        string $tokenName
    ) {
        $data = $request->validate([
            'tenDangNhap' => 'required|string',
            'matKhau' => 'required|string',
        ]);

        $taiKhoan = TaiKhoan::where(
            'tenDangNhap',
            $data['tenDangNhap']
        )->first();

        if (!$taiKhoan) {
            return response()->json([
                'message' =>
                    'Tên đăng nhập hoặc mật khẩu không đúng'
            ], 401);
        }

        if (!Hash::check(
            $data['matKhau'],
            $taiKhoan->matKhau
        )) {
            return response()->json([
                'message' =>
                    'Tên đăng nhập hoặc mật khẩu không đúng'
            ], 401);
        }

        if (!in_array($taiKhoan->vaiTro, $roles)) {
            return response()->json([
                'message' =>
                    'Bạn không có quyền đăng nhập tại đây'
            ], 403);
        }

        if ($taiKhoan->trangThai !== 'HOAT_DONG') {
            return response()->json([
                'message' =>
                    'Tài khoản đã bị khóa'
            ], 403);
        }

        $token = $taiKhoan
            ->createToken(
                $tokenName,
                [$taiKhoan->vaiTro]
            )
            ->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công',

            'token' => $token,

            'taiKhoan' => [
                'maTK' => $taiKhoan->maTK,
                'tenDangNhap' =>
                    $taiKhoan->tenDangNhap,
                'vaiTro' => $taiKhoan->vaiTro,
            ],
        ]);
    }


    // =========================
    // THÔNG TIN NGƯỜI ĐĂNG NHẬP
    // =========================
    public function me(Request $request)
    {
        $taiKhoan = $request->user();

        if ($taiKhoan->vaiTro === 'KHACH_HANG') {
            $taiKhoan->load('khachHang');
        } else {
            $taiKhoan->load('nhanVien');
        }

        return response()->json([
            'taiKhoan' => $taiKhoan
        ]);
    }


    // =========================
    // ĐĂNG XUẤT
    // =========================
    public function logout(Request $request)
    {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'message' => 'Đăng xuất thành công'
        ]);
    }
}