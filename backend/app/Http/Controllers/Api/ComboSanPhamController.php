<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ComboSanPhamController extends Controller
{
        private function kiemTraQuyenQuanLy(Request $request): void
    {
        $taiKhoan = $request->user();

        abort_unless($taiKhoan, 401, 'Bạn cần đăng nhập.');

        abort_unless(
            $taiKhoan->vaiTro === 'QUAN_LY'
                && $taiKhoan->trangThai === 'HOAT_DONG',
            403,
            'Chỉ quản lý đang hoạt động được thực hiện thao tác này.'
        );
    }
    private function quyTacDuLieu(bool $capNhat = false): array
{
    $batBuoc = $capNhat ? 'sometimes' : 'required';

    return [
        'tenCombo' => [$batBuoc, 'required', 'string', 'max:255'],
        'donGia' => [
            $batBuoc,
            'required',
            'numeric',
            'min:0',
            'max:99999999.99',
            'decimal:0,2',
        ],
        'moTa' => ['sometimes', 'nullable', 'string', 'max:5000'],
        'hinhAnh' => ['sometimes', 'nullable', 'string', 'max:255'],
        'trangThai' => [
            'sometimes',
            'required',
            Rule::in(['HOAT_DONG', 'NGUNG_HOAT_DONG']),
        ],
        'sanPhams' => [$batBuoc, 'required', 'array', 'min:1', 'max:100'],
        'sanPhams.*' => ['required', 'array:maSP,soLuong'],
        'sanPhams.*.maSP' => [
            'required',
            'string',
            'distinct',
            Rule::exists('san_phams', 'maSP')
                ->where('trangThai', 'HOAT_DONG'),
        ],
        'sanPhams.*.soLuong' => [
            'required',
            'integer',
            'min:1',
            'max:1000',
        ],
    ];
}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Combo::with('chiTietCombos.sanPham')
            ->where('trangThai', 'HOAT_DONG');

        // Tìm kiếm theo tên combo nếu có keyword
        if ($request->filled('keyword')) {
            $query->where(
                'tenCombo',
                'like',
                '%' . $data['keyword'] . '%'
            );
        }

        $combos = $query
            ->orderBy('maCombo')
            ->paginate(10)
            ->withQueryString();

        return response()->json($combos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->kiemTraQuyenQuanLy($request);

        $data = $request->validate($this->quyTacDuLieu());

        $combo = DB::transaction(function () use ($data) {
            $sanPhams = $data['sanPhams'];

            // sanPhams là dữ liệu bảng chi tiết, không phải cột của combos
            unset($data['sanPhams']);

            $combo = Combo::create([
                ...$data,
                'maCombo' => 'CB' . Str::ulid(),
                'trangThai' => $data['trangThai'] ?? 'HOAT_DONG',
            ]);

            foreach ($sanPhams as $sanPham) {
                $combo->chiTietCombos()->create([
                    'maCTCombo' => 'CTCB' . Str::ulid(),
                    'maSP' => $sanPham['maSP'],
                    'soLuong' => $sanPham['soLuong'],
                ]);
            }

            return $combo->load('chiTietCombos.sanPham');
        });

        return response()->json([
            'message' => 'Thêm combo thành công.',
            'data' => $combo,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $maCombo)
    {
        $combo = Combo::with('chiTietCombos.sanPham')
            ->where('trangThai', 'HOAT_DONG')
            ->where('maCombo', $maCombo)
            ->firstOrFail();

        return response()->json([
            'message' => 'Lấy chi tiết combo thành công.',
            'data' => $combo,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $maCombo)
    {
        $this->kiemTraQuyenQuanLy($request);

        $data = $request->validate($this->quyTacDuLieu(true));

        if ($data === []) {
            return response()->json([
                'message' => 'Bạn cần gửi ít nhất một trường hợp lệ để cập nhật.',
            ], 422);
        }

        $combo = DB::transaction(function () use ($data, $maCombo) {
            $combo = Combo::where('maCombo', $maCombo)
                ->lockForUpdate()
                ->firstOrFail();

            $sanPhams = $data['sanPhams'] ?? null;

            unset($data['sanPhams']);

            if ($data !== []) {
                $combo->update($data);
            }

            if ($sanPhams !== null) {
                $combo->chiTietCombos()->delete();

                foreach ($sanPhams as $sanPham) {
                    $combo->chiTietCombos()->create([
                        'maCTCombo' => 'CTCB' . Str::ulid(),
                        'maSP' => $sanPham['maSP'],
                        'soLuong' => $sanPham['soLuong'],
                    ]);
                }
            }

            return $combo->load('chiTietCombos.sanPham');
        });

        return response()->json([
            'message' => 'Cập nhật combo thành công.',
            'data' => $combo,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $maCombo)
    {
        $this->kiemTraQuyenQuanLy($request);

        DB::transaction(function () use ($maCombo) {
            $combo = Combo::where('maCombo', $maCombo)
                ->lockForUpdate()
                ->firstOrFail();

            $combo->update([
                'trangThai' => 'NGUNG_HOAT_DONG',
            ]);
        });

        return response()->json([
            'message' => 'Đã ngừng bán combo.',
        ]);
    }
}
