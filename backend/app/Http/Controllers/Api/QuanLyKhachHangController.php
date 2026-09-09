<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\KhachHang;
use App\Models\OrderAccess;
use App\Models\TaiKhoan;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuanLyKhachHangController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        OrderAccess::staff($request, true);
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'trangThai' => ['nullable', Rule::in(['HOAT_DONG', 'KHOA'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $base = DB::table('khach_hangs as kh')
            ->join('tai_khoans as tk', 'tk.maKH', '=', 'kh.maKH')
            ->where('tk.vaiTro', 'KHACH_HANG');
        $total = (clone $base)->count();
        $active = (clone $base)->where('tk.trangThai', 'HOAT_DONG')->where('kh.trangThai', 'HOAT_DONG')->count();
        $query = clone $base;
        if (($data['trangThai'] ?? '') === 'HOAT_DONG') {
            $query->where('tk.trangThai', 'HOAT_DONG')->where('kh.trangThai', 'HOAT_DONG');
        } elseif (($data['trangThai'] ?? '') === 'KHOA') {
            $query->where(function (Builder $query): void {
                $query->where('tk.trangThai', '<>', 'HOAT_DONG')->orWhere('kh.trangThai', '<>', 'HOAT_DONG');
            });
        }
        if (($search = trim($data['q'] ?? '')) !== '') {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(function (Builder $query) use ($pattern): void {
                foreach (['kh.maKH', 'kh.hoTen', 'kh.email', 'kh.soDienThoai', 'tk.tenDangNhap'] as $column) {
                    $query->orWhereRaw($column." LIKE ? ESCAPE '!'", [$pattern]);
                }
            });
        }
        $customers = $query->select(['kh.maKH', 'kh.hoTen', 'kh.email', 'kh.soDienThoai', 'kh.ngayDangKy', 'tk.tenDangNhap'])
            ->selectRaw("CASE WHEN tk.trangThai = 'HOAT_DONG' AND kh.trangThai = 'HOAT_DONG' THEN 'HOAT_DONG' ELSE 'KHOA' END as trangThai")
            ->orderByDesc('kh.ngayDangKy')->orderBy('kh.maKH')->paginate(5);

        return response()->json([...$customers->toArray(), 'stats' => ['total' => $total, 'active' => $active, 'locked' => $total - $active]]);
    }

    public function history(Request $request, string $maKH): JsonResponse
    {
        OrderAccess::staff($request, true);
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);
        KhachHang::findOrFail($maKH);
        $orders = DonHang::where('maKH', $maKH);
        $paidTotal = (clone $orders)->where('trangThai', 'DA_THANH_TOAN')->sum('tongTien');
        $history = $orders->select(['maDonHang', 'ngayDat', 'tongTien', 'trangThai', 'kieuDat'])
            ->withCount('veGhes')->withSum('chiTietComboDonHangs', 'soLuong')
            ->orderByDesc('ngayDat')->orderByDesc('maDonHang')->paginate(10);

        return response()->json([...$history->toArray(), 'paidTotal' => (float) $paidTotal]);
    }

    public function status(Request $request, string $maKH): JsonResponse
    {
        OrderAccess::staff($request, true);
        $data = $request->validate(['trangThai' => ['required', Rule::in(['HOAT_DONG', 'KHOA'])]]);
        DB::transaction(function () use ($maKH, $data): void {
            $customer = KhachHang::whereKey($maKH)->lockForUpdate()->firstOrFail();
            $account = TaiKhoan::where('maKH', $maKH)->where('vaiTro', 'KHACH_HANG')->lockForUpdate()->firstOrFail();
            $customer->update($data);
            $account->update($data);
            if ($data['trangThai'] === 'KHOA') {
                $account->tokens()->delete();
            }
        }, 3);

        return response()->json(['message' => $data['trangThai'] === 'KHOA' ? 'Đã khóa tài khoản và thu hồi phiên đăng nhập.' : 'Đã mở khóa tài khoản.', 'trangThai' => $data['trangThai']]);
    }

    public function destroy(Request $request, string $maKH): JsonResponse
    {
        OrderAccess::staff($request, true);
        DB::transaction(function () use ($maKH): void {
            $customer = KhachHang::whereKey($maKH)->lockForUpdate()->firstOrFail();
            $account = TaiKhoan::where('maKH', $maKH)->where('vaiTro', 'KHACH_HANG')->lockForUpdate()->firstOrFail();
            $customer->update(['trangThai' => 'DA_XOA']);
            $account->tokens()->delete();
            $account->delete();
        }, 3);

        return response()->json(['message' => 'Đã xóa tài khoản và thu hồi phiên đăng nhập. Hồ sơ và lịch sử giao dịch được giữ lại.']);
    }
}
