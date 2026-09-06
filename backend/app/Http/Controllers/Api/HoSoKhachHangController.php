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

class HoSoKhachHangController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => OrderAccess::customer($request)]);
    }

    public function update(Request $request): JsonResponse
    {
        $customer = OrderAccess::customer($request);
        $data = $request->validate([
            'hoTen' => ['sometimes', 'required', 'string', 'max:255'],
            'soDienThoai' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('khach_hangs', 'soDienThoai')->ignore($customer->maKH, 'maKH')],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('khach_hangs', 'email')->ignore($customer->maKH, 'maKH')],
            'ngaySinh' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'gioiTinh' => ['sometimes', 'nullable', Rule::in(['NAM', 'NU'])],
        ]);
        abort_if($data === [], 422, 'Chưa có thông tin cập nhật.');
        $customer = DB::transaction(function () use ($customer, $data) {
            $customer = KhachHang::whereKey($customer->maKH)->lockForUpdate()->firstOrFail();
            abort_unless($customer->trangThai === 'HOAT_DONG', 403);
            $customer->update($data);

            return $customer;
        }, 3);

        return response()->json(['data' => $customer]);
    }

    public function password(Request $request): JsonResponse
    {
        OrderAccess::customer($request);
        $data = $request->validate([
            'matKhauHienTai' => ['required', 'string', 'max:255'],
            'matKhauMoi' => ['required', 'string', 'min:8', 'max:72', 'confirmed', 'different:matKhauHienTai'],
        ]);
        DB::transaction(function () use ($request, $data) {
            $account = TaiKhoan::whereKey($request->user()->maTK)->lockForUpdate()->firstOrFail();
            abort_unless($account->trangThai === 'HOAT_DONG', 403);
            try {
                $matches = Hash::check($data['matKhauHienTai'], $account->matKhau);
            } catch (\RuntimeException) {
                $matches = false;
            }
            if (! $matches) {
                throw ValidationException::withMessages(['matKhauHienTai' => 'Mật khẩu hiện tại không đúng.']);
            }
            $account->update(['matKhau' => Hash::make($data['matKhauMoi'])]);
            $account->tokens()->delete();
        }, 3);

        return response()->json(['message' => 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.']);
    }
}
