<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderAccess;
use App\Models\SanPham;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $data = $request->validate($this->rules());

        return response()->json(['data' => SanPham::create([...$data, 'maSP' => 'SP'.Str::ulid(), 'trangThai' => $data['trangThai'] ?? 'HOAT_DONG'])], 201);
    }

    public function update(Request $request, string $maSP): JsonResponse
    {
        OrderAccess::staff($request, true);
        $data = $request->validate($this->rules(true));
        abort_if($data === [], 422, 'Chưa có dữ liệu cập nhật.');
        $product = DB::transaction(function () use ($data, $maSP) {
            $product = SanPham::whereKey($maSP)->lockForUpdate()->firstOrFail();
            $product->update($data);

            return $product;
        }, 3);

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

    private function rules(bool $update = false): array
    {
        $required = $update ? 'sometimes' : 'required';

        return [
            'tenSP' => [$required, 'required', 'string', 'max:255'],
            'loaiSP' => [$required, 'required', Rule::in(['BAP', 'NUOC', 'DO_AN'])],
            'donGia' => [$required, 'required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'moTa' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'hinhAnh' => ['sometimes', 'nullable', 'string', 'max:255'],
            'trangThai' => ['sometimes', 'required', Rule::in(['HOAT_DONG', 'NGUNG_HOAT_DONG'])],
        ];
    }
}
