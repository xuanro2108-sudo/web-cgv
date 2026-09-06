<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderAccess;
use App\Models\PhongChieu;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhongChieuController extends Controller
{
    // Danh sách + tìm kiếm phòng chiếu
    public function index(Request $request)
    {
        $query = PhongChieu::with('soDoGhe');

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $query->where(function ($q) use ($keyword) {
                $q->where('maPhong', 'like', "%{$keyword}%")
                    ->orWhere('tenPhong', 'like', "%{$keyword}%");
            });
        }

        $phongChieus = $query
            ->orderBy('maPhong')
            ->paginate(10);

        return response()->json($phongChieus);
    }

    // Xem thông tin phòng
    public function show(string $maPhong)
    {
        $phongChieu = PhongChieu::with('soDoGhe')
            ->findOrFail($maPhong);

        return response()->json([
            'message' => 'Lấy thông tin phòng chiếu thành công',
            'data' => $phongChieu,
        ]);
    }

    // Chỉ sửa tên và trạng thái
    public function update(Request $request, string $maPhong)
    {
        OrderAccess::staff($request);

        $phongChieu = PhongChieu::findOrFail($maPhong);

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
            'message' => 'Cập nhật phòng chiếu thành công',
            'data' => $phongChieu->fresh()->load('soDoGhe'),
        ]);
    }
}
