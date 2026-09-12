<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\OrderAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ComboSanPhamController extends Controller
{
    public function management(Request $request): JsonResponse
    {
        OrderAccess::staff($request);
        $data = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'trangThai' => ['nullable', Rule::in(['HOAT_DONG', 'NGUNG_HOAT_DONG'])],
        ]);
        $query = Combo::with('chiTietCombos.sanPham');
        if ($request->filled('keyword')) {
            $query->where('tenCombo', 'like', '%'.$data['keyword'].'%');
        }
        if ($request->filled('trangThai')) {
            $query->where('trangThai', $data['trangThai']);
        }

        return response()->json($query->orderByDesc('created_at')->orderBy('maCombo')->paginate(10)->withQueryString());
    }

    private function thongBaoDuLieu(): array
    {
        return [
            'maCombo.required' => 'Vui lòng nhập mã combo.',
            'maCombo.unique' => 'Mã combo đã trùng với một combo khác. Vui lòng nhập mã combo khác.',
            'maCombo.regex' => 'Mã combo chỉ được chứa chữ cái không dấu, số, dấu gạch ngang và gạch dưới.',
            'maCombo.max' => 'Mã combo không được vượt quá 50 ký tự.',
            'tenCombo.required' => 'Vui lòng nhập tên combo.',
            'tenCombo.max' => 'Tên combo không được vượt quá 255 ký tự.',
            'donGia.required' => 'Vui lòng nhập giá combo.',
            'donGia.numeric' => 'Giá combo phải là số.',
            'donGia.min' => 'Giá combo không được nhỏ hơn 0.',
            'donGia.max' => 'Giá combo không được vượt quá 99.999.999,99 đồng.',
            'donGia.decimal' => 'Giá combo chỉ được có tối đa 2 chữ số thập phân.',
            'sanPhams.required' => 'Combo phải có ít nhất một sản phẩm.',
            'sanPhams.min' => 'Combo phải có ít nhất một sản phẩm.',
            'sanPhams.*.maSP.required' => 'Vui lòng chọn sản phẩm cho từng dòng.',
            'sanPhams.*.maSP.distinct' => 'Sản phẩm bị trùng. Hãy tăng số lượng trên cùng một dòng.',
            'sanPhams.*.maSP.exists' => 'Sản phẩm đã ngừng bán hoặc không tồn tại. Vui lòng chọn sản phẩm khác.',
            'sanPhams.*.soLuong.required' => 'Vui lòng nhập số lượng sản phẩm.',
            'sanPhams.*.soLuong.integer' => 'Số lượng sản phẩm phải là số nguyên.',
            'sanPhams.*.soLuong.min' => 'Số lượng sản phẩm phải từ 1 trở lên.',
            'sanPhams.*.soLuong.max' => 'Số lượng mỗi sản phẩm không được vượt quá 1000.',
            'anh.image' => 'File đã chọn phải là ảnh.',
            'anh.mimes' => 'Ảnh phải có định dạng JPG, PNG hoặc WebP.',
            'anh.max' => 'Ảnh không được lớn hơn 2 MB.',
        ];
    }

    private function luuAnh(Request $request, array $data, \Closure $save): Combo
    {
        unset($data['anh']);
        $path = $request->hasFile('anh') ? $request->file('anh')->store('combo-images', 'public') : null;
        abort_if($path === false, 500, 'Không thể lưu ảnh combo.');
        try {
            if ($path) {
                $data['hinhAnh'] = url('/storage/'.$path);
            }

            return $save($data);
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $error;
        }
    }

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
            'maCombo' => $capNhat ? ['exclude'] : ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:combos,maCombo'],
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
            'anh' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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
                '%'.$data['keyword'].'%'
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

        $data = $request->validate($this->quyTacDuLieu(), $this->thongBaoDuLieu());

        $combo = $this->luuAnh($request, $data, fn (array $data): Combo => DB::transaction(function () use ($data) {
            $sanPhams = $data['sanPhams'];

            // sanPhams là dữ liệu bảng chi tiết, không phải cột của combos
            unset($data['sanPhams']);

            $combo = Combo::create([
                ...$data,
                'trangThai' => $data['trangThai'] ?? 'HOAT_DONG',
            ]);

            foreach ($sanPhams as $sanPham) {
                $combo->chiTietCombos()->create([
                    'maCTCombo' => 'CTCB'.Str::ulid(),
                    'maSP' => $sanPham['maSP'],
                    'soLuong' => $sanPham['soLuong'],
                ]);
            }

            return $combo->load('chiTietCombos.sanPham');
        }));

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

        $data = $request->validate($this->quyTacDuLieu(true), $this->thongBaoDuLieu());

        if ($data === []) {
            return response()->json([
                'message' => 'Bạn cần gửi ít nhất một trường hợp lệ để cập nhật.',
            ], 422);
        }

        $combo = $this->luuAnh($request, $data, fn (array $data): Combo => DB::transaction(function () use ($data, $maCombo) {
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
                        'maCTCombo' => 'CTCB'.Str::ulid(),
                        'maSP' => $sanPham['maSP'],
                        'soLuong' => $sanPham['soLuong'],
                    ]);
                }
            }

            return $combo->load('chiTietCombos.sanPham');
        }));

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
