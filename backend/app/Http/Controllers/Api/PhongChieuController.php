<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderAccess;
use App\Models\PhongChieu;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhongChieuController extends Controller
{
    // =====================================================
    // DANH SÁCH PHÒNG CHIẾU
    // Tìm kiếm theo mã / tên
    // Lọc theo trạng thái
    // =====================================================
    public function index(Request $request)
    {
        $query = PhongChieu::with('soDoGhe');

        // Tìm kiếm
        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);

            $query->where(function ($q) use ($keyword) {
                $q->where(
                    'maPhong',
                    'like',
                    "%{$keyword}%"
                )
                    ->orWhere(
                        'tenPhong',
                        'like',
                        "%{$keyword}%"
                    );
            });
        }

        // Lọc trạng thái
        if ($request->filled('trangThai')) {
            $query->where(
                'trangThai',
                $request->trangThai
            );
        }

        $phongChieus = $query
            ->orderBy('maPhong')
            ->paginate(10)
            ->withQueryString();

        return response()->json(
            $phongChieus
        );
    }

    // =====================================================
    // CHI TIẾT PHÒNG CHIẾU
    // =====================================================
    public function show(string $maPhong)
    {
        $phongChieu =
            PhongChieu::with('soDoGhe')
                ->findOrFail($maPhong);

        return response()->json([
            'message' =>
                'Lấy thông tin phòng chiếu thành công',
            'data' => $phongChieu,
        ]);
    }

    // =====================================================
    // CẬP NHẬT PHÒNG CHIẾU
    // Chỉ thay đổi tên phòng + trạng thái
    // Không sửa sức chứa trực tiếp
    // =====================================================
    public function update(
        Request $request,
        string $maPhong
    ) {
        OrderAccess::staff($request);

        $phongChieu =
            PhongChieu::findOrFail(
                $maPhong
            );

        $data = $request->validate([
            'tenPhong' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'trangThai' => [
                'sometimes',
                'required',

                Rule::in([
                    'HOAT_DONG',
                    'NGUNG_HOAT_DONG',
                ]),
            ],
        ]);

        $phongChieu->update($data);

        return response()->json([
            'message' =>
                'Cập nhật phòng chiếu thành công',

            'data' =>
                $phongChieu
                    ->fresh()
                    ->load('soDoGhe'),
        ]);
    }
}