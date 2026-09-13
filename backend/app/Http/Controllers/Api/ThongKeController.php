<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThongKeController extends Controller
{
    private function validateDate(
        Request $request,
        array $extra = []
    ): array {
        return $request->validate(
            array_merge([
                'tuNgay' => [
                    'required',
                    'date',
                ],

                'denNgay' => [
                    'required',
                    'date',
                    'after_or_equal:tuNgay',
                ],
            ], $extra),
            [
                'tuNgay.required' =>
                    'Vui lòng chọn từ ngày.',

                'denNgay.required' =>
                    'Vui lòng chọn đến ngày.',

                'denNgay.after_or_equal' =>
                    'Đến ngày phải lớn hơn hoặc bằng từ ngày.',
            ]
        );
    }


    public function danhSachPhim()
    {
        return response()->json([
            'data' =>
                DB::table('phims')
                    ->select(
                        'maPhim',
                        'tenPhim'
                    )
                    ->orderBy('tenPhim')
                    ->get(),
        ]);
    }


    public function danhSachKhuyenMai()
    {
        return response()->json([
            'data' =>
                DB::table('khuyen_mais')
                    ->select(
                        'maKM',
                        'tenKM'
                    )
                    ->orderBy('tenKM')
                    ->get(),
        ]);
    }


    public function doanhThuVe(
        Request $request
    ) {
        $data =
            $this->validateDate(
                $request
            );

        $query =
            DB::table(
                've_ghes as vg'
            )
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
                ->where(
                    'tt.trangThai',
                    'THANH_CONG'
                )
                ->where(
                    'vg.trangThai',
                    'DA_DAT'
                )
                ->whereBetween(
                    'tt.ngayThanhToan',
                    [
                        $data['tuNgay']
                            . ' 00:00:00',

                        $data['denNgay']
                            . ' 23:59:59',
                    ]
                );

        $tongDoanhThu =
            (clone $query)
                ->sum(
                    'vg.giaVe'
                );

        $tongSoVe =
            (clone $query)
                ->count(
                    'vg.maVe'
                );

        $theoNgay =
            (clone $query)
                ->selectRaw('
                    DATE(tt.ngayThanhToan) as ngay,
                    COUNT(vg.maVe) as soVe,
                    SUM(vg.giaVe) as doanhThu
                ')
                ->groupByRaw(
                    'DATE(tt.ngayThanhToan)'
                )
                ->orderBy('ngay')
                ->get();

        return response()->json([
            'tongDoanhThu' =>
                (float) $tongDoanhThu,

            'tongSoVe' =>
                (int) $tongSoVe,

            'coDuLieu' =>
                $tongSoVe > 0,

            'theoNgay' =>
                $theoNgay,
        ]);
    }


    public function doanhThuCombo(
        Request $request
    ) {
        $data =
            $this->validateDate(
                $request
            );

        $query =
            DB::table(
                'chi_tiet_combo_don_hangs as ct'
            )
                ->join(
                    'don_hangs as dh',
                    'dh.maDonHang',
                    '=',
                    'ct.maDonHang'
                )
                ->join(
                    'thanh_toans as tt',
                    'tt.maDonHang',
                    '=',
                    'dh.maDonHang'
                )
                ->join(
                    'combos as c',
                    'c.maCombo',
                    '=',
                    'ct.maCombo'
                )
                ->where(
                    'tt.trangThai',
                    'THANH_CONG'
                )
                ->whereBetween(
                    'tt.ngayThanhToan',
                    [
                        $data['tuNgay']
                            . ' 00:00:00',

                        $data['denNgay']
                            . ' 23:59:59',
                    ]
                );

        $tongDoanhThu =
            (clone $query)
                ->sum(
                    'ct.thanhTien'
                );

        $tongSoLuong =
            (clone $query)
                ->sum(
                    'ct.soLuong'
                );

        $chiTiet =
            (clone $query)
                ->selectRaw('
                    c.maCombo,
                    c.tenCombo,
                    SUM(ct.soLuong) as soLuong,
                    SUM(ct.thanhTien) as doanhThu
                ')
                ->groupBy(
                    'c.maCombo',
                    'c.tenCombo'
                )
                ->orderByDesc(
                    'doanhThu'
                )
                ->get();

        return response()->json([
            'tongDoanhThu' =>
                (float) $tongDoanhThu,

            'tongSoLuong' =>
                (int) $tongSoLuong,

            'coDuLieu' =>
                $tongSoLuong > 0,

            'chiTiet' =>
                $chiTiet,
        ]);
    }


    public function theoPhim(
        Request $request
    ) {
        $data =
            $this->validateDate(
                $request,
                [
                    'maPhim' => [
                        'nullable',
                        'string',
                        'max:50',
                    ],
                ]
            );

        $banVe =
            DB::table(
                've_ghes as vg'
            )
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
                ->join(
                    'lich_chieus as lc',
                    'lc.maLichChieu',
                    '=',
                    'vg.maLichChieu'
                )
                ->where(
                    'tt.trangThai',
                    'THANH_CONG'
                )
                ->where(
                    'vg.trangThai',
                    'DA_DAT'
                )
                ->whereBetween(
                    'lc.ngayChieu',
                    [
                        $data['tuNgay'],
                        $data['denNgay'],
                    ]
                );

        if (
            !empty(
                $data['maPhim']
            )
        ) {
            $banVe->where(
                'lc.maPhim',
                $data['maPhim']
            );
        }

        $banVe =
            $banVe
                ->selectRaw('
                    lc.maPhim,
                    COUNT(vg.maVe) as soVe,
                    SUM(vg.giaVe) as doanhThu
                ')
                ->groupBy(
                    'lc.maPhim'
                );


        $sucChua =
            DB::table(
                'lich_chieus as lc'
            )
                ->join(
                    'phong_chieus as pc',
                    'pc.maPhong',
                    '=',
                    'lc.maPhong'
                )
                ->whereBetween(
                    'lc.ngayChieu',
                    [
                        $data['tuNgay'],
                        $data['denNgay'],
                    ]
                );

        if (
            !empty(
                $data['maPhim']
            )
        ) {
            $sucChua->where(
                'lc.maPhim',
                $data['maPhim']
            );
        }

        $sucChua =
            $sucChua
                ->selectRaw('
                    lc.maPhim,
                    SUM(pc.sucChua) as tongCho
                ')
                ->groupBy(
                    'lc.maPhim'
                );


        $query =
            DB::table(
                'phims as p'
            )
                ->joinSub(
                    $sucChua,
                    'sc',
                    function ($join) {
                        $join->on(
                            'sc.maPhim',
                            '=',
                            'p.maPhim'
                        );
                    }
                )
                ->leftJoinSub(
                    $banVe,
                    'bv',
                    function ($join) {
                        $join->on(
                            'bv.maPhim',
                            '=',
                            'p.maPhim'
                        );
                    }
                )
                ->selectRaw('
                    p.maPhim,
                    p.tenPhim,
                    COALESCE(bv.soVe, 0) as soVe,
                    COALESCE(bv.doanhThu, 0) as doanhThu,
                    sc.tongCho,
                    ROUND(
                        CASE
                            WHEN sc.tongCho > 0
                            THEN COALESCE(bv.soVe, 0) * 100 / sc.tongCho
                            ELSE 0
                        END,
                        2
                    ) as tyLeLapGhe
                ');

        if (
            !empty(
                $data['maPhim']
            )
        ) {
            $query->where(
                'p.maPhim',
                $data['maPhim']
            );
        }

        $chiTiet =
            $query
                ->orderByDesc(
                    'doanhThu'
                )
                ->get();


        $tongDoanhThu =
            (float)
            $chiTiet->sum(
                'doanhThu'
            );

        $tongSoVe =
            (int)
            $chiTiet->sum(
                'soVe'
            );

        $tongCho =
            (int)
            $chiTiet->sum(
                'tongCho'
            );

        $tyLeLapGhe =
            $tongCho > 0
                ? round(
                    $tongSoVe
                    * 100
                    / $tongCho,
                    2
                )
                : 0;

        return response()->json([
            'tongDoanhThu' =>
                $tongDoanhThu,

            'tongSoVe' =>
                $tongSoVe,

            'tyLeLapGhe' =>
                $tyLeLapGhe,

            'coDuLieu' =>
                $chiTiet->count() > 0,

            'chiTiet' =>
                $chiTiet,
        ]);
    }


    public function khuyenMai(
        Request $request
    ) {
        $data =
            $this->validateDate(
                $request,
                [
                    'maKM' => [
                        'nullable',
                        'string',
                        'max:50',
                    ],
                ]
            );


        $tienVe =
            DB::table(
                've_ghes'
            )
                ->where(
                    'trangThai',
                    'DA_DAT'
                )
                ->selectRaw('
                    maDonHang,
                    SUM(giaVe) as tienVe
                ')
                ->groupBy(
                    'maDonHang'
                );


        $tienCombo =
            DB::table(
                'chi_tiet_combo_don_hangs'
            )
                ->selectRaw('
                    maDonHang,
                    SUM(thanhTien) as tienCombo
                ')
                ->groupBy(
                    'maDonHang'
                );


        $query =
            DB::table(
                'don_hangs as dh'
            )
                ->join(
                    'thanh_toans as tt',
                    'tt.maDonHang',
                    '=',
                    'dh.maDonHang'
                )
                ->join(
                    'khuyen_mais as km',
                    'km.maKM',
                    '=',
                    'dh.maKM'
                )
                ->leftJoinSub(
                    $tienVe,
                    'tv',
                    function ($join) {
                        $join->on(
                            'tv.maDonHang',
                            '=',
                            'dh.maDonHang'
                        );
                    }
                )
                ->leftJoinSub(
                    $tienCombo,
                    'tc',
                    function ($join) {
                        $join->on(
                            'tc.maDonHang',
                            '=',
                            'dh.maDonHang'
                        );
                    }
                )
                ->where(
                    'tt.trangThai',
                    'THANH_CONG'
                )
                ->whereBetween(
                    'tt.ngayThanhToan',
                    [
                        $data['tuNgay']
                            . ' 00:00:00',

                        $data['denNgay']
                            . ' 23:59:59',
                    ]
                );


        if (
            !empty(
                $data['maKM']
            )
        ) {
            $query->where(
                'dh.maKM',
                $data['maKM']
            );
        }


        $chiTiet =
            $query
                ->selectRaw('
                    km.maKM,
                    km.tenKM,

                    COUNT(
                        DISTINCT dh.maDonHang
                    ) as soLuotSuDung,

                    SUM(
                        GREATEST(
                            COALESCE(tv.tienVe, 0)
                            +
                            COALESCE(tc.tienCombo, 0)
                            -
                            tt.soTien,
                            0
                        )
                    ) as tongTienGiam,

                    SUM(
                        tt.soTien
                    ) as doanhThu
                ')
                ->groupBy(
                    'km.maKM',
                    'km.tenKM'
                )
                ->orderByDesc(
                    'soLuotSuDung'
                )
                ->get();


        $tongLuotSuDung =
            (int)
            $chiTiet->sum(
                'soLuotSuDung'
            );

        $tongTienGiam =
            (float)
            $chiTiet->sum(
                'tongTienGiam'
            );

        $tongDoanhThu =
            (float)
            $chiTiet->sum(
                'doanhThu'
            );


        return response()->json([
            'tongLuotSuDung' =>
                $tongLuotSuDung,

            'tongTienGiam' =>
                $tongTienGiam,

            'tongDoanhThu' =>
                $tongDoanhThu,

            'coDuLieu' =>
                $tongLuotSuDung > 0,

            'chiTiet' =>
                $chiTiet,
        ]);
    }
}