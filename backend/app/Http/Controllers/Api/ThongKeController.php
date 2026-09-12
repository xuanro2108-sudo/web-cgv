<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThongKeController extends Controller
{
    public function doanhThuVe(Request $request)
    {
        $data = $request->validate([
            'tuNgay' => ['required', 'date'],
            'denNgay' => ['required', 'date', 'after_or_equal:tuNgay'],
        ], [
            'tuNgay.required' => 'Vui lòng chọn từ ngày.',
            'denNgay.required' => 'Vui lòng chọn đến ngày.',
            'denNgay.after_or_equal' => 'Đến ngày phải lớn hơn hoặc bằng từ ngày.',
        ]);

        $query = DB::table('ve_ghes as vg')
            ->join(
                'don_hangs as dh',
                'dh.maDonHang',
                '=',
                'vg.maDonHang'
            )
            ->join(
                'thanh_toans as tt',
                'tt.maDonHang',
                '=',
                'dh.maDonHang'
            )
            ->where('tt.trangThai', 'THANH_CONG')
            ->where('vg.trangThai', 'DA_DAT')
            ->whereBetween('tt.ngayThanhToan', [
                $data['tuNgay'] . ' 00:00:00',
                $data['denNgay'] . ' 23:59:59',
            ]);

        $tongDoanhThu = (clone $query)
            ->sum('vg.giaVe');

        $tongSoVe = (clone $query)
            ->count('vg.maVe');

        $theoNgay = (clone $query)
            ->selectRaw('
                DATE(tt.ngayThanhToan) as ngay,
                COUNT(vg.maVe) as soVe,
                SUM(vg.giaVe) as doanhThu
            ')
            ->groupByRaw('DATE(tt.ngayThanhToan)')
            ->orderBy('ngay')
            ->get();

        return response()->json([
            'tongDoanhThu' => (float) $tongDoanhThu,
            'tongSoVe' => $tongSoVe,
            'coDuLieu' => $tongSoVe > 0,
            'theoNgay' => $theoNgay,
        ]);
    }
}