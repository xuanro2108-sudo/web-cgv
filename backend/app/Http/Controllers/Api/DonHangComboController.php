<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\OrderAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DonHangComboController extends Controller
{
    public function store(Request $request, string $maDonHang): JsonResponse
    {
        $data = $request->validate(['maCombo' => ['required', 'string'], 'soLuong' => ['required', 'integer', 'min:1', 'max:100']]);

        return $this->save($request, $maDonHang, $data['maCombo'], (int) $data['soLuong'], true);
    }

    public function update(Request $request, string $maDonHang, string $maCombo): JsonResponse
    {
        $data = $request->validate(['soLuong' => ['required', 'integer', 'min:1', 'max:100']]);

        return $this->save($request, $maDonHang, $maCombo, (int) $data['soLuong'], false);
    }

    private function save(Request $request, string $orderId, string $comboId, int $quantity, bool $creating): JsonResponse
    {
        $order = DB::transaction(function () use ($request, $orderId, $comboId, $quantity, $creating) {
            $order = OrderAccess::owned($request, $orderId);
            OrderAccess::editable($order);
            $combo = Combo::where('maCombo', $comboId)->lockForUpdate()->firstOrFail();
            abort_unless($combo->trangThai === 'HOAT_DONG', 422, 'Combo đã ngừng bán.');
            abort_if($combo->chiTietCombos()->whereHas('sanPham', fn ($q) => $q->where('trangThai', '!=', 'HOAT_DONG'))->exists(), 422, 'Sản phẩm trong combo đã ngừng bán.');
            $line = $order->chiTietComboDonHangs()->where('maCombo', $comboId)->first();
            if ($creating) {
                abort_if($line, 409, 'Combo đã có trong đơn. Hãy cập nhật số lượng.');
                $line = $order->chiTietComboDonHangs()->make(['maChiTiet' => 'CT'.Str::ulid(), 'maCombo' => $comboId, 'donGia' => $combo->donGia]);
            } else {
                abort_unless($line, 404);
            }
            $line->fill(['soLuong' => $quantity, 'thanhTien' => number_format((int) round((float) $line->donGia * 100) * $quantity / 100, 2, '.', '')])->save();
            $order->update(['tongTien' => $order->tinhTongTien()]);

            return $order->load('chiTietComboDonHangs.combo');
        }, 3);

        return response()->json(['data' => $order], $creating ? 201 : 200);
    }

    public function destroy(Request $request, string $maDonHang, string $maCombo): JsonResponse
    {
        $order = DB::transaction(function () use ($request, $maDonHang, $maCombo) {
            $order = OrderAccess::owned($request, $maDonHang);
            OrderAccess::editable($order);
            $order->chiTietComboDonHangs()->where('maCombo', $maCombo)->firstOrFail()->delete();
            $order->update(['tongTien' => $order->tinhTongTien()]);

            return $order->load('chiTietComboDonHangs.combo');
        }, 3);

        return response()->json(['data' => $order]);
    }
}
