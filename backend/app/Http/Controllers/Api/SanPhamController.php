<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderAccess;
use App\Models\SanPham;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SanPhamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->listing($request, false);
    }

    public function management(Request $request): JsonResponse
    {
        OrderAccess::staff($request, true);

        return $this->listing($request, true);
    }

    private function listing(Request $request, bool $all): JsonResponse
    {
        $data = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'loaiSP' => ['nullable', Rule::in(['BAP', 'NUOC', 'DO_AN'])],
            'trangThai' => ['nullable', Rule::in(['HOAT_DONG', 'NGUNG_HOAT_DONG'])],
        ]);
        $query = SanPham::query();
        if (! $all) {
            $query->where('trangThai', 'HOAT_DONG');
        } elseif ($request->filled('trangThai')) {
            $query->where('trangThai', $data['trangThai']);
        }
        if ($request->filled('keyword')) {
            $query->where('tenSP', 'like', '%'.$data['keyword'].'%');
        }
        if ($request->filled('loaiSP')) {
            $query->where('loaiSP', $data['loaiSP']);
        }

        return response()->json($query->orderBy('maSP')->paginate(20)->withQueryString());
    }

    public function show(string $maSP): JsonResponse
    {
        return response()->json(['data' => SanPham::where('trangThai', 'HOAT_DONG')->findOrFail($maSP)]);
    }

    public function store(Request $request): JsonResponse
    {
        OrderAccess::staff($request, true);
        $data = $request->validate($this->rules(), $this->messages());

        $product = $this->saveImage($request, $data, fn (array $data): SanPham => SanPham::create([...$data, 'trangThai' => $data['trangThai'] ?? 'HOAT_DONG']));

        return response()->json(['data' => $product], 201);
    }

    public function update(Request $request, string $maSP): JsonResponse
    {
        OrderAccess::staff($request, true);
        $data = $request->validate($this->rules(true), $this->messages());
        abort_if($data === [], 422, 'Chưa có dữ liệu cập nhật.');
        $product = $this->saveImage($request, $data, fn (array $data): SanPham => DB::transaction(function () use ($data, $maSP) {
            $product = SanPham::whereKey($maSP)->lockForUpdate()->firstOrFail();
            $product->update($data);

            return $product;
        }, 3));

        return response()->json(['data' => $product]);
    }

    public function destroy(Request $request, string $maSP): JsonResponse
    {
        OrderAccess::staff($request, true);
        $product = DB::transaction(function () use ($maSP) {
            $product = SanPham::whereKey($maSP)->lockForUpdate()->firstOrFail();
            $product->update(['trangThai' => 'NGUNG_HOAT_DONG']);

            return $product;
        }, 3);

        return response()->json(['message' => 'Đã ngừng bán sản phẩm.', 'data' => $product]);
    }

    private function saveImage(Request $request, array $data, \Closure $save): SanPham
    {
        unset($data['anh']);
        $path = $request->hasFile('anh') ? $request->file('anh')->store('product-images', 'public') : null;
        abort_if($path === false, 500, 'Không thể lưu ảnh sản phẩm.');
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

    private function messages(): array
    {
        return [
            'maSP.required' => 'Vui lòng nhập mã sản phẩm.',
            'maSP.unique' => 'Mã sản phẩm đã trùng với một sản phẩm khác. Vui lòng nhập mã sản phẩm khác.',
            'maSP.regex' => 'Mã sản phẩm chỉ được chứa chữ cái không dấu, số, dấu gạch ngang và gạch dưới.',
            'maSP.max' => 'Mã sản phẩm không được vượt quá 50 ký tự.',
            'tenSP.required' => 'Vui lòng nhập tên sản phẩm.',
            'tenSP.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',
            'loaiSP.required' => 'Vui lòng chọn loại sản phẩm.',
            'loaiSP.in' => 'Loại sản phẩm phải là bắp, nước hoặc đồ ăn.',
            'donGia.required' => 'Vui lòng nhập giá bán.',
            'donGia.numeric' => 'Giá bán phải là số.',
            'donGia.min' => 'Giá bán không được nhỏ hơn 0.',
            'donGia.max' => 'Giá bán không được vượt quá 99.999.999,99 đồng.',
            'donGia.decimal' => 'Giá bán chỉ được có tối đa 2 chữ số thập phân.',
            'moTa.max' => 'Mô tả không được vượt quá 5000 ký tự.',
            'trangThai.in' => 'Trạng thái sản phẩm không hợp lệ.',
            'anh.image' => 'File đã chọn phải là ảnh.',
            'anh.mimes' => 'Ảnh phải có định dạng JPG, PNG hoặc WebP.',
            'anh.max' => 'Ảnh không được lớn hơn 2 MB.',
        ];
    }

    private function rules(bool $update = false): array
    {
        $required = $update ? 'sometimes' : 'required';

        return [
            'maSP' => $update ? ['exclude'] : ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:san_phams,maSP'],
            'tenSP' => [$required, 'required', 'string', 'max:255'],
            'loaiSP' => [$required, 'required', Rule::in(['BAP', 'NUOC', 'DO_AN'])],
            'donGia' => [$required, 'required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'moTa' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'hinhAnh' => ['sometimes', 'nullable', 'string', 'max:255'],
            'anh' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'trangThai' => ['sometimes', 'required', Rule::in(['HOAT_DONG', 'NGUNG_HOAT_DONG'])],
        ];
    }
}
