<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HuyDonController extends Controller
{
    public function __invoke(Request $request, string $maDonHang): JsonResponse
    {
        $order = DB::transaction(function () use ($request, $maDonHang) {
            $order = OrderAccess::owned($request, $maDonHang);
            if (in_array($order->trangThai, ['DA_HUY', 'HET_HAN'], true)) {
                return $order;
            }
            abort_unless($order->trangThai === 'CHO_THANH_TOAN', 409, 'Đơn đã thanh toán không thể hủy tại đây.');
            $order->huy($order->daHetHan() ? 'HET_HAN' : 'DA_HUY');

            return $order;
        }, 3);

        return response()->json(['message' => 'Đơn đã hủy hoặc hết hạn.', 'data' => $order]);
    }
}
