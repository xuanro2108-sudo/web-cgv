<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ghe;
use App\Models\OrderAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GheController extends Controller
{
    // Danh sách ghế + tìm kiếm
    public function index(Request $request)
    {
        $query = Ghe::with('soDoGhe');

        // Tìm theo mã ghế
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $query->where('maGhe', 'like', "%{$keyword}%");
        }

        // Lọc theo sơ đồ ghế
        if ($request->filled('maSoDo')) {
            $query->where('maSoDo', $request->maSoDo);
        }

        // Lọc theo trạng thái
        if ($request->filled('trangThai')) {
            $query->where('trangThai', $request->trangThai);
        }

        $ghes = $query
            ->orderBy('maSoDo')
            ->orderBy('hang')
            ->orderBy('cot')
            ->paginate(100);

        return response()->json($ghes);
    }

    // Xem thông tin một ghế
    public function show(string $maGhe)
    {
        $ghe = Ghe::with('soDoGhe')
            ->findOrFail($maGhe);

        return response()->json([
            'message' => 'Lấy thông tin ghế thành công',
            'data' => $ghe,
        ]);
    }

    // Chỉ được sửa trạng thái ghế
    public function update(Request $request, string $maGhe)
    {
        OrderAccess::staff($request);

        $ghe = Ghe::findOrFail($maGhe);

        $data = $request->validate([
            'trangThai' => [
                'required',
                Rule::in([
                    'HOAT_DONG',
                    'KHOA',
                ]),
            ],
        ]);

        $ghe->update($data);

        return response()->json([
            'message' => 'Cập nhật trạng thái ghế thành công',
            'data' => $ghe->fresh()->load('soDoGhe'),
        ]);
    }
}
