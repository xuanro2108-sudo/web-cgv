<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KhuyenMai;
use App\Models\OrderAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class KhuyenMaiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(KhuyenMai::where('trangThai', 'HOAT_DONG')->whereDate('ngayBatDau', '<=', today())->whereDate('ngayKetThuc', '>=', today())->orderBy('maKM')->paginate(20));
    }

    public function show(string $maKM): JsonResponse
    {
        $promotion = KhuyenMai::where('trangThai', 'HOAT_DONG')->whereDate('ngayBatDau', '<=', today())->whereDate('ngayKetThuc', '>=', today())->findOrFail($maKM);

        return response()->json(['data' => $promotion]);
    }

    public function store(Request $request): JsonResponse
    {
        OrderAccess::staff($request, true);
        $data = $this->validated($request);
        $data['maKM'] = $request->validate(['maKM' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', 'unique:khuyen_mais,maKM']])['maKM'];

        return response()->json(['data' => KhuyenMai::create($data)], 201);
    }

    public function update(Request $request, string $maKM): JsonResponse
    {
        OrderAccess::staff($request, true);
        $promotion = DB::transaction(function () use ($request, $maKM) {
            $promotion = KhuyenMai::whereKey($maKM)->lockForUpdate()->firstOrFail();
            $data = $this->validated($request, $promotion);
            if ($promotion->donHangs()->exists()) {
                abort_if(count(array_diff(array_keys($data), ['tenKM', 'trangThai'])) > 0, 409, 'Mã đã được dùng; hãy tạo mã mới để thay đổi điều kiện giảm.');
            }
            $promotion->update($data);

            return $promotion;
        }, 3);

        return response()->json(['data' => $promotion]);
    }

    public function destroy(Request $request, string $maKM): JsonResponse
    {
        OrderAccess::staff($request, true);
        $promotion = DB::transaction(function () use ($maKM) {
            $promotion = KhuyenMai::whereKey($maKM)->lockForUpdate()->firstOrFail();
            $promotion->update(['trangThai' => 'NGUNG_HOAT_DONG']);

            return $promotion;
        }, 3);

        return response()->json(['data' => $promotion]);
    }

    private function validated(Request $request, ?KhuyenMai $promotion = null): array
    {
        $required = $promotion ? 'sometimes' : 'required';
        $data = $request->validate([
            'tenKM' => [$required, 'required', 'string', 'max:255'],
            'hinhThuc' => [$required, 'required', Rule::in(['GIAM_PHAN_TRAM', 'GIAM_GIA'])],
            'giaTri' => [$required, 'required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'donToiThieu' => [$required, 'required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'ngayBatDau' => [$required, 'required', 'date_format:Y-m-d'],
            'ngayKetThuc' => [$required, 'required', 'date_format:Y-m-d'],
            'trangThai' => ['sometimes', 'required', Rule::in(['HOAT_DONG', 'NGUNG_HOAT_DONG'])],
        ]);
        abort_if($data === [], 422, 'Chưa có dữ liệu cập nhật.');
        $merged = array_merge($promotion ? [
            'hinhThuc' => $promotion->hinhThuc, 'giaTri' => $promotion->giaTri,
            'ngayBatDau' => $promotion->ngayBatDau->format('Y-m-d'), 'ngayKetThuc' => $promotion->ngayKetThuc->format('Y-m-d'),
        ] : [], $data);
        Validator::make($merged, [
            'ngayKetThuc' => ['after_or_equal:ngayBatDau'],
            'giaTri' => $merged['hinhThuc'] === 'GIAM_PHAN_TRAM' ? ['max:100', 'numeric'] : ['numeric'],
        ])->validate();

        return $data;
    }
}
