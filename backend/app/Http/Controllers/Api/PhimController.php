<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderAccess;
use App\Models\Phim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        OrderAccess::staff($request);

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
            'anh' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'trailer' => ['nullable', 'url'],
            'trangThai' => ['required', Rule::in(['SAP_CHIEU', 'DANG_CHIEU', 'DA_CHIEU'])],
        ], [
            'maPhim.unique' => 'Mã phim đã trùng với một phim khác. Vui lòng nhập mã phim khác.',
        ]);

        $phim = $this->saveWithPoster($request, new Phim, $data);

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
        OrderAccess::staff($request);

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
            'anh' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'trailer' => ['nullable', 'url'],
            'trangThai' => ['sometimes', 'required', Rule::in(['SAP_CHIEU', 'DANG_CHIEU', 'DA_CHIEU'])],
        ]);

        $this->saveWithPoster($request, $phim, $data);

        return response()->json([
            'message' => 'Cập nhật phim thành công',
            'data' => $phim->fresh(),
        ]);
    }

    private function saveWithPoster(Request $request, Phim $phim, array $data): Phim
    {
        unset($data['anh']);
        $path = $request->hasFile('anh') ? $request->file('anh')->store('phim-posters', 'public') : null;
        abort_if($path === false, 500, 'Không thể lưu ảnh poster.');

        try {
            if ($path) {
                $data['hinhAnh'] = url('/storage/'.$path);
            }
            $phim->fill($data)->save();
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $error;
        }

        return $phim;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $maPhim)
    {
        OrderAccess::staff($request);

        $phim = Phim::findOrFail($maPhim);
        $phim->delete();

        return response()->json([
            'message' => 'Xóa phim thành công',
        ]);
    }
}
