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
            'hoTen' => [
                'required',
                'string',
                'max:255',
            ],

            'soDienThoai' => [
                'required',
                'regex:/^0[0-9]{9}$/',
                'unique:khach_hangs,soDienThoai',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'regex:/^[A-Za-z0-9._%+\-]+@gmail\.com$/i',
                'unique:khach_hangs,email',
            ],

            'ngaySinh' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'gioiTinh' => [
                'nullable',
                'in:NAM,NU',
            ],

            'matKhau' => [
                'required',
                'string',
                'min:6',
                'confirmed',
            ],
        ]);

        // Chuẩn hóa email
        $data['email'] = strtolower(
            trim($data['email'])
        );

        $result = DB::transaction(function () use ($data) {

            // =========================
            // SINH MÃ KHÁCH HÀNG
            // =========================
            $lastKhachHang = KhachHang::orderBy(
                'maKH',
                'desc'
            )->first();

            $nextKH = $lastKhachHang
                ? ((int) substr(
                    $lastKhachHang->maKH,
                    2
                )) + 1
                : 1;

            $maKH = 'KH' . str_pad(
                $nextKH,
                3,
                '0',
                STR_PAD_LEFT
            );

            // =========================
            // SINH MÃ TÀI KHOẢN
            // =========================
            $lastTaiKhoan = TaiKhoan::orderBy(
                'maTK',
                'desc'
            )->first();

            $nextTK = $lastTaiKhoan
                ? ((int) substr(
                    $lastTaiKhoan->maTK,
                    2
                )) + 1
                : 1;

            $maTK = 'TK' . str_pad(
                $nextTK,
                3,
                '0',
                STR_PAD_LEFT
            );

            // =========================
            // TẠO KHÁCH HÀNG
            // =========================
            $khachHang = KhachHang::create([
                'maKH' => $maKH,

                'hoTen' =>
                    $data['hoTen'],

                'soDienThoai' =>
                    $data['soDienThoai'],

                'email' =>
                    $data['email'],

                'ngaySinh' =>
                    $data['ngaySinh'] ?? null,

                'gioiTinh' =>
                    $data['gioiTinh'] ?? null,

                'ngayDangKy' =>
                    now()->toDateString(),

                'trangThai' =>
                    'HOAT_DONG',
            ]);

            // =========================
            // TẠO TÀI KHOẢN
            // Email được dùng làm
            // tenDangNhap khách hàng
            // =========================
            $taiKhoan = TaiKhoan::create([
                'maTK' =>
                    $maTK,

                'tenDangNhap' =>
                    $data['email'],

                'matKhau' =>
                    Hash::make(
                        $data['matKhau']
                    ),

                'vaiTro' =>
                    'KHACH_HANG',

                'maKH' =>
                    $maKH,

                'maNV' =>
                    null,

                'trangThai' =>
                    'HOAT_DONG',
            ]);

            return [
                'khachHang' =>
                    $khachHang,

                'taiKhoan' =>
                    $taiKhoan,
            ];
        });

        return response()->json([
            'message' =>
                'Đăng ký thành công',

            'khachHang' =>
                $result['khachHang'],

            'taiKhoan' =>
                $result['taiKhoan'],
        ], 201);
    }


    // =========================
    // ĐĂNG NHẬP KHÁCH HÀNG
    // EMAIL HOẶC SĐT
    // =========================
    public function loginCustomer(Request $request)
    {
        $data = $request->validate([
            'identifier' => [
                'required',
                'string',
            ],

            'matKhau' => [
                'required',
                'string',
            ],
        ]);

        $identifier = trim(
            $data['identifier']
        );

        // Nếu nhập email thì đưa về chữ thường
        if (filter_var(
            $identifier,
            FILTER_VALIDATE_EMAIL
        )) {
            $identifier = strtolower(
                $identifier
            );
        }

        // =========================
        // TÌM KHÁCH HÀNG
        // =========================
        $khachHang = KhachHang::where(
            'email',
            $identifier
        )
            ->orWhere(
                'soDienThoai',
                $identifier
            )
            ->first();

        if (!$khachHang) {
            return response()->json([
                'message' =>
                    'Email, số điện thoại hoặc mật khẩu không đúng'
            ], 401);
        }

        // =========================
        // TÌM TÀI KHOẢN
        // =========================
        $taiKhoan = TaiKhoan::where(
            'maKH',
            $khachHang->maKH
        )
            ->where(
                'vaiTro',
                'KHACH_HANG'
            )
            ->first();

        if (!$taiKhoan) {
            return response()->json([
                'message' =>
                    'Email, số điện thoại hoặc mật khẩu không đúng'
            ], 401);
        }

        // =========================
        // KIỂM TRA MẬT KHẨU
        // =========================
        if (!Hash::check(
            $data['matKhau'],
            $taiKhoan->matKhau
        )) {
            return response()->json([
                'message' =>
                    'Email, số điện thoại hoặc mật khẩu không đúng'
            ], 401);
        }

        // =========================
        // KIỂM TRA TRẠNG THÁI
        // =========================
        if (
            $taiKhoan->trangThai !==
            'HOAT_DONG'
        ) {
            return response()->json([
                'message' =>
                    'Tài khoản đã bị khóa'
            ], 403);
        }

        // =========================
        // TẠO TOKEN
        // =========================
        $token = $taiKhoan
            ->createToken(
                'customer',
                [$taiKhoan->vaiTro]
            )
            ->plainTextToken;

        return response()->json([
            'message' =>
                'Đăng nhập thành công',

            'token' =>
                $token,

            'taiKhoan' => [
                'maTK' =>
                    $taiKhoan->maTK,

                'tenDangNhap' =>
                    $taiKhoan->tenDangNhap,

                'vaiTro' =>
                    $taiKhoan->vaiTro,
            ],

            'khachHang' =>
                $khachHang,
        ]);
    }


    // =========================
    // LOGIN NHÂN VIÊN / QUẢN LÝ
    // =========================
    public function loginInternal(Request $request)
    {
        return $this->loginInternalAccount(
            $request,
            [
                'NHAN_VIEN',
                'QUAN_LY',
            ],
            'dashboard'
        );
    }


    // =========================
    // XỬ LÝ LOGIN NỘI BỘ
    // =========================
    private function loginInternalAccount(
        Request $request,
        array $roles,
        string $tokenName
    ) {
        $data = $request->validate([
            'tenDangNhap' => [
                'required',
                'string',
            ],

            'matKhau' => [
                'required',
                'string',
            ],
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

        if (!in_array(
            $taiKhoan->vaiTro,
            $roles,
            true
        )) {
            return response()->json([
                'message' =>
                    'Bạn không có quyền đăng nhập tại đây'
            ], 403);
        }

        if (
            $taiKhoan->trangThai !==
            'HOAT_DONG'
        ) {
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
            'message' =>
                'Đăng nhập thành công',

            'token' =>
                $token,

            'taiKhoan' => [
                'maTK' =>
                    $taiKhoan->maTK,

                'tenDangNhap' =>
                    $taiKhoan->tenDangNhap,

                'vaiTro' =>
                    $taiKhoan->vaiTro,
            ],
        ]);
    }


    // =========================
    // THÔNG TIN TÀI KHOẢN
    // =========================
    public function me(Request $request)
    {
        $taiKhoan =
            $request->user();

        if (
            $taiKhoan->vaiTro ===
            'KHACH_HANG'
        ) {
            $taiKhoan->load(
                'khachHang'
            );
        } else {
            $taiKhoan->load(
                'nhanVien'
            );
        }

        return response()->json([
            'taiKhoan' =>
                $taiKhoan
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
            'message' =>
                'Đăng xuất thành công'
        ]);
    }
}