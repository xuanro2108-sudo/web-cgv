<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KhachHang;
use App\Models\OrderAccess;
use App\Models\TaiKhoan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function changeInternalPassword(Request $request): JsonResponse
    {
        $account = OrderAccess::staff($request);
        $data = $request->validate([
            'matKhauHienTai' => ['required', 'string', 'max:255'],
            'matKhauMoi' => ['required', 'string', 'min:8', 'max:72', 'confirmed', 'different:matKhauHienTai'],
        ], [
            'matKhauHienTai.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'matKhauHienTai.string' => 'Mật khẩu hiện tại không hợp lệ.',
            'matKhauHienTai.max' => 'Mật khẩu hiện tại không được vượt quá 255 ký tự.',
            'matKhauMoi.required' => 'Vui lòng nhập mật khẩu mới.',
            'matKhauMoi.string' => 'Mật khẩu mới không hợp lệ.',
            'matKhauMoi.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'matKhauMoi.max' => 'Mật khẩu mới không được vượt quá 72 ký tự.',
            'matKhauMoi.confirmed' => 'Mật khẩu xác nhận chưa khớp.',
            'matKhauMoi.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ]);
        DB::transaction(function () use ($account, $data): void {
            $locked = TaiKhoan::whereKey($account->maTK)->lockForUpdate()->firstOrFail();
            abort_unless($locked->trangThai === 'HOAT_DONG' && in_array($locked->vaiTro, ['QUAN_LY', 'NHAN_VIEN'], true), 403);
            if (! Hash::check($data['matKhauHienTai'], $locked->matKhau)) {
                throw ValidationException::withMessages(['matKhauHienTai' => 'Mật khẩu hiện tại không đúng.']);
            }
            $locked->update(['matKhau' => Hash::make($data['matKhauMoi'])]);
            $locked->tokens()->delete();
        });

        return response()->json(['message' => 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.']);
    }

    public function updateInternalProfile(Request $request): JsonResponse
    {
        $account = OrderAccess::staff($request);
        $employee = $account->nhanVien;
        abort_unless($employee && $employee->trangThai === 'DANG_LAM', 403, 'Không tìm thấy hồ sơ nhân viên đang làm việc.');
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }
        $data = $request->validate([
            'hoTen' => ['required', 'string', 'max:255'],
            'sdt' => ['bail', 'required', 'string', 'regex:/^0[0-9]{9}$/', Rule::unique('nhan_viens', 'sdt')->ignore($employee->maNV, 'maNV')],
            'email' => ['bail', 'required', 'email', 'max:255', 'regex:/^[A-Za-z0-9._%+\-]+@gmail\.com$/i', Rule::unique('nhan_viens', 'email')->ignore($employee->maNV, 'maNV'), Rule::unique('tai_khoans', 'tenDangNhap')->ignore($account->maTK, 'maTK')],
        ], [
            'hoTen.required' => 'Vui lòng nhập họ và tên.',
            'hoTen.string' => 'Họ và tên không hợp lệ.',
            'hoTen.max' => 'Họ và tên không được vượt quá 255 ký tự.',
            'sdt.required' => 'Vui lòng nhập số điện thoại.',
            'sdt.string' => 'Số điện thoại không hợp lệ.',
            'sdt.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.',
            'sdt.unique' => 'Số điện thoại đã được sử dụng.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.regex' => 'Vui lòng sử dụng địa chỉ Gmail hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'email.unique' => 'Email đã được sử dụng.',
        ]);
        DB::transaction(function () use ($employee, $account, $data): void {
            $employee->update($data);
            $account->update(['tenDangNhap' => $data['email']]);
        });

        return response()->json(['taiKhoan' => $account->fresh('nhanVien')]);
    }

    // =========================
    // ĐĂNG KÝ KHÁCH HÀNG
    // =========================
    public function register(Request $request)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }

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
                'required',
                'date',
                'before:today',
            ],

            'gioiTinh' => [
                'required',
                'in:NAM,NU',
            ],

            'matKhau' => [
                'required',
                'string',
                'min:6',
                'confirmed',
            ],
        ], [
            'ngaySinh.required' => 'Vui lòng chọn ngày sinh.',
            'ngaySinh.date' => 'Ngày sinh không hợp lệ.',
            'ngaySinh.before' => 'Ngày sinh phải nhỏ hơn ngày hiện tại.',
            'gioiTinh.required' => 'Vui lòng chọn giới tính.',
            'gioiTinh.in' => 'Vui lòng chọn giới tính hợp lệ.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.regex' => 'Vui lòng sử dụng địa chỉ Gmail hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'email.unique' => 'Email đã được sử dụng. Vui lòng chọn email khác.',
            'soDienThoai.unique' => 'Số điện thoại đã được sử dụng. Vui lòng chọn số điện thoại khác.',
        ]);

        // Chuẩn hóa email
        $data['email'] = strtolower(
            trim($data['email'])
        );

        $result = DB::transaction(function () use ($data) {

            // =========================
            // SINH MÃ KHÁCH HÀNG
            // =========================
            $maKH = $this->nextSequentialCode(
                KhachHang::class,
                'maKH',
                'KH'
            );

            // =========================
            // SINH MÃ TÀI KHOẢN
            // =========================
            $maTK = $this->nextSequentialCode(
                TaiKhoan::class,
                'maTK',
                'TK'
            );

            // =========================
            // TẠO KHÁCH HÀNG
            // =========================
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

            // =========================
            // TẠO TÀI KHOẢN
            // Email được dùng làm
            // tenDangNhap khách hàng
            // =========================
            $taiKhoan = TaiKhoan::create([
                'maTK' => $maTK,

                'tenDangNhap' => $data['email'],

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

    private function nextSequentialCode(
        string $modelClass,
        string $column,
        string $prefix
    ): string {
        $highestNumber = 0;
        $pattern = '/^'.preg_quote($prefix, '/').'([0-9]+)$/';

        foreach ($modelClass::query()->pluck($column) as $existingCode) {
            if (preg_match($pattern, (string) $existingCode, $matches) === 1) {
                $highestNumber = max($highestNumber, (int) $matches[1]);
            }
        }

        return $prefix.str_pad(
            (string) ($highestNumber + 1),
            3,
            '0',
            STR_PAD_LEFT
        );
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

        if (! $khachHang) {
            return response()->json([
                'message' => 'Email, số điện thoại hoặc mật khẩu không đúng',
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

        if (! $taiKhoan) {
            return response()->json([
                'message' => 'Email, số điện thoại hoặc mật khẩu không đúng',
            ], 401);
        }

        // =========================
        // KIỂM TRA MẬT KHẨU
        // =========================
        if (! Hash::check(
            $data['matKhau'],
            $taiKhoan->matKhau
        )) {
            return response()->json([
                'message' => 'Email, số điện thoại hoặc mật khẩu không đúng',
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
                'message' => 'Tài khoản đã bị khóa',
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
            'message' => 'Đăng nhập thành công',

            'token' => $token,

            'taiKhoan' => [
                'maTK' => $taiKhoan->maTK,

                'tenDangNhap' => $taiKhoan->tenDangNhap,

                'vaiTro' => $taiKhoan->vaiTro,
            ],

            'khachHang' => $khachHang,
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
    private function loginInternalAccount(Request $request, array $roles, string $tokenName)
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'regex:/^[A-Za-z0-9._%+\-]+@gmail\.com$/i',
            ],
            'matKhau' => ['required', 'string'],
        ]);

        $email = strtolower(trim($data['email']));

        $taiKhoan = TaiKhoan::where('tenDangNhap', $email)->first();

        if (! $taiKhoan || ! Hash::check($data['matKhau'], $taiKhoan->matKhau)) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không đúng',
            ], 401);
        }

        if (! in_array($taiKhoan->vaiTro, $roles, true)) {
            return response()->json([
                'message' => 'Bạn không có quyền đăng nhập tại đây',
            ], 403);
        }

        if ($taiKhoan->trangThai !== 'HOAT_DONG') {
            return response()->json([
                'message' => 'Tài khoản đã bị khóa',
            ], 403);
        }

        $token = $taiKhoan
            ->createToken($tokenName, [$taiKhoan->vaiTro])
            ->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'token' => $token,
            'taiKhoan' => [
                'maTK' => $taiKhoan->maTK,
                'tenDangNhap' => $taiKhoan->tenDangNhap,
                'vaiTro' => $taiKhoan->vaiTro,
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
            'taiKhoan' => $taiKhoan,
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
            'message' => 'Đăng xuất thành công',
        ]);
    }
}
