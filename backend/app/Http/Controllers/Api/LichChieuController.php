<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LichChieu;
use App\Models\OrderAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LichChieuController extends Controller
{
    /**
     * Danh sách lịch chiếu
     */
    public function index()
    {
        $lichChieus = LichChieu::with(['phim', 'phongChieu'])
            ->orderBy('ngayChieu')
            ->orderBy('gioBatDau')
            ->paginate(10);

        return response()->json($lichChieus);
    }

    /**
     * Thêm lịch chiếu
     */
    public function store(Request $request)
    {
        OrderAccess::staff($request);

        $data = $request->validate([
            'maLichChieu' => ['required', 'string', 'max:50', 'unique:lich_chieus,maLichChieu'],
            'maPhim' => ['required', 'exists:phims,maPhim'],
            'maPhong' => ['required', 'exists:phong_chieus,maPhong'],
            'ngayChieu' => ['required', 'date'],
            'gioBatDau' => ['required', 'date_format:H:i'],
            'gioKetThuc' => ['required', 'date_format:H:i', 'after:gioBatDau'],
            'giaVeCoBan' => ['required', 'numeric', 'min:0'],
            'trangThai' => [
                'required',
                Rule::in(['HOAT_DONG', 'NGUNG_HOAT_DONG']),
            ],
        ]);

        $lichChieu = LichChieu::create($data);

        return response()->json([
            'message' => 'Thêm lịch chiếu thành công',
            'data' => $lichChieu->load(['phim', 'phongChieu']),
        ], 201);
    }

    /**
     * Xem thông tin một lịch chiếu
     */
    public function show(string $maLichChieu)
    {
        $lichChieu = LichChieu::with(['phim', 'phongChieu'])
            ->findOrFail($maLichChieu);

        return response()->json([
            'message' => 'Lấy thông tin lịch chiếu thành công',
            'data' => $lichChieu,
        ]);
    }

    /**
     * Cập nhật lịch chiếu
     */
    public function update(Request $request, string $maLichChieu)
    {
        OrderAccess::staff($request);

        $lichChieu = LichChieu::findOrFail($maLichChieu);

        $data = $request->validate([
            'maPhim' => ['sometimes', 'required', 'exists:phims,maPhim'],
            'maPhong' => ['sometimes', 'required', 'exists:phong_chieus,maPhong'],
            'ngayChieu' => ['sometimes', 'required', 'date'],
            'gioBatDau' => ['sometimes', 'required', 'date_format:H:i'],
            'gioKetThuc' => ['sometimes', 'required', 'date_format:H:i'],
            'giaVeCoBan' => ['sometimes', 'required', 'numeric', 'min:0'],
            'trangThai' => [
                'sometimes',
                'required',
                Rule::in(['HOAT_DONG', 'NGUNG_HOAT_DONG']),
            ],
        ]);

        $gioBatDau = Carbon::createFromFormat(
            'H:i',
            $data['gioBatDau'] ?? $lichChieu->gioBatDau->format('H:i')
        );
        $gioKetThuc = Carbon::createFromFormat(
            'H:i',
            $data['gioKetThuc'] ?? $lichChieu->gioKetThuc->format('H:i')
        );

        if ($gioKetThuc->lessThanOrEqualTo($gioBatDau)) {
            throw ValidationException::withMessages([
                'gioKetThuc' => 'gioKetThuc phải sau gioBatDau.',
            ]);
        }

        $lichChieu->update($data);

        return response()->json([
            'message' => 'Cập nhật lịch chiếu thành công',
            'data' => $lichChieu->fresh()->load(['phim', 'phongChieu']),
        ]);
    }

    /**
     * Xóa lịch chiếu
     */
    public function destroy(Request $request, string $maLichChieu)
    {
        OrderAccess::staff($request);

        $lichChieu = LichChieu::findOrFail($maLichChieu);

        $lichChieu->delete();

        return response()->json([
            'message' => 'Xóa lịch chiếu thành công',
        ]);
    }
}
