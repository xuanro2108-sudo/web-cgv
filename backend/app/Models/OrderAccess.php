<?php

namespace App\Models;

use Illuminate\Http\Request;

class OrderAccess
{
    public static function customer(Request $request): KhachHang
    {
        $account = $request->user();
        abort_unless($account, 401);
        abort_unless($account->vaiTro === 'KHACH_HANG' && $account->trangThai === 'HOAT_DONG', 403);
        $customer = $account->khachHang;
        abort_unless($customer && $customer->trangThai === 'HOAT_DONG', 403);

        return $customer;
    }

    public static function staff(Request $request, bool $managerOnly = false): TaiKhoan
    {
        $account = $request->user();
        abort_unless($account, 401);
        abort_unless($account->trangThai === 'HOAT_DONG' && in_array($account->vaiTro, $managerOnly ? ['QUAN_LY'] : ['QUAN_LY', 'NHAN_VIEN'], true), 403);

        return $account;
    }

    /** Call inside a transaction before modifying an order or its children. */
    public static function owned(Request $request, string $id): DonHang
    {
        return DonHang::where('maKH', self::customer($request)->maKH)
            ->where('maDonHang', $id)->lockForUpdate()->firstOrFail();
    }

    public static function editable(DonHang $order): void
    {
        abort_unless($order->trangThai === 'CHO_THANH_TOAN', 409, 'Đơn không còn được phép chỉnh sửa.');
        abort_if($order->thanhToan()->where('trangThai', 'CHO_THANH_TOAN')->exists(), 409, 'Đơn đang có yêu cầu thanh toán.');
    }
}
