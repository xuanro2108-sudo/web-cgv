<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Phim;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhimController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $phims = Phim::orderByDesc('created_at')->paginate(10);

        return response()->json($phims);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'maPhim' => ['required', 'string', 'max:50', 'unique:phims,maPhim'],
            'tenPhim' => ['required', 'string', 'max:255'],
            'theLoai' => ['nullable', 'string', 'max:255'],
            'thoiLuong' => ['nullable', 'integer', 'min:1'],
            'daoDien' => ['nullable', 'string', 'max:255'],
            'dienVien' => ['nullable', 'string'],
            'ngayKhoiChieu' => ['nullable', 'date'],
            'ngayKetThuc' => ['nullable', 'date', 'after_or_equal:ngayKhoiChieu'],
            'moTa' => ['nullable', 'string'],
            'hinhAnh' => ['nullable', 'string', 'max:255'],
            'trailer' => ['nullable', 'url'],
            'trangThai' => ['required', Rule::in(['SAP_CHIEU', 'DANG_CHIEU', 'DA_CHIEU'])],
        ]);

        $phim = Phim::create($data);

        return response()->json([
            'message' => 'Thêm phim thành công',
            'data' => $phim,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $maPhim)
    {
        $phim = Phim::findOrFail($maPhim);

        return response()->json([
            'message' => 'Lấy thông tin phim thành công',
            'data' => $phim,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $maPhim)
    {
        $phim = Phim::findOrFail($maPhim);

        $data = $request->validate([
            'tenPhim' => ['sometimes', 'required', 'string', 'max:255'],
            'theLoai' => ['nullable', 'string', 'max:255'],
            'thoiLuong' => ['nullable', 'integer', 'min:1'],
            'daoDien' => ['nullable', 'string', 'max:255'],
            'dienVien' => ['nullable', 'string'],
            'ngayKhoiChieu' => ['nullable', 'date'],
            'ngayKetThuc' => ['nullable', 'date', 'after_or_equal:ngayKhoiChieu'],
            'moTa' => ['nullable', 'string'],
            'hinhAnh' => ['nullable', 'string', 'max:255'],
            'trailer' => ['nullable', 'url'],
            'trangThai' => ['sometimes', 'required', Rule::in(['SAP_CHIEU', 'DANG_CHIEU', 'DA_CHIEU'])],
        ]);

        $phim->update($data);

        return response()->json([
            'message' => 'Cập nhật phim thành công',
            'data' => $phim->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $maPhim)
    {
        $phim = Phim::findOrFail($maPhim);
        $phim->delete();

        return response()->json([
            'message' => 'Xóa phim thành công',
        ]);
    }
}
