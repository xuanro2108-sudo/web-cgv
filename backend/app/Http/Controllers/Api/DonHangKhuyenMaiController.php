<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KhuyenMai;
use App\Models\OrderAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DonHangKhuyenMaiController extends Controller
{
    public function check(Request $request, string $maDonHang): JsonResponse
    {
        return $this->apply($request, $maDonHang, false);
    }

    public function store(Request $request, string $maDonHang): JsonResponse
    {
        return $this->apply($request, $maDonHang, true);
    }

    private function apply(Request $request, string $id, bool $save): JsonResponse
    {
        $data = $request->validate(['maKM' => ['required', 'string', 'max:50']]);
        $result = DB::transaction(function () use ($request, $id, $data, $save) {
            $order = OrderAccess::owned($request, $id);
            OrderAccess::editable($order);
            $promotion = KhuyenMai::whereKey($data['maKM'])->lockForUpdate()->firstOrFail();
            $subtotal = $order->tamTinh();
            abort_unless($promotion->apDungDuoc($subtotal), 422, 'Mã hết hiệu lực hoặc đơn chưa đạt mức tối thiểu.');
            $discount = $promotion->tienGiam($subtotal);
            if ($save) {
                $order->update(['maKM' => $promotion->maKM, 'tongTien' => round($subtotal - $discount, 2)]);
            }

            return ['maKM' => $promotion->maKM, 'tamTinh' => $subtotal, 'tienGiam' => $discount, 'tongTien' => round($subtotal - $discount, 2), 'daApDung' => $save];
        }, 3);

        return response()->json(['data' => $result]);
    }

    public function destroy(Request $request, string $maDonHang): JsonResponse
    {
        $order = DB::transaction(function () use ($request, $maDonHang) {
            $order = OrderAccess::owned($request, $maDonHang);
            OrderAccess::editable($order);
            $order->update(['maKM' => null, 'tongTien' => $order->tamTinh()]);

            return $order;
        }, 3);

        return response()->json(['data' => $order]);
    }
}
