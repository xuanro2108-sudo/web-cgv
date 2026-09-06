<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\OrderAccess;
use App\Models\ThanhToan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ThanhToanController extends Controller
{
    private function mockEnabled(): void
    {
        abort_unless(app()->environment(['local', 'testing']) && config('payments.mock_enabled'), 404);
    }

    public function store(Request $request, string $maDonHang): JsonResponse
    {
        $data = $request->validate(['phuongThuc' => ['required', 'in:TIEN_MAT,GIA_LAP']]);
        if ($data['phuongThuc'] === 'GIA_LAP') {
            $this->mockEnabled();
        }
        $payment = DB::transaction(function () use ($request, $maDonHang, $data) {
            $order = OrderAccess::owned($request, $maDonHang);
            abort_unless($order->trangThai === 'CHO_THANH_TOAN' && ! $order->daHetHan(), 409, 'Đơn không còn được thanh toán.');
            $payment = $order->thanhToan()->lockForUpdate()->first();
            if ($payment && $payment->trangThai === 'CHO_THANH_TOAN') {
                abort_unless($payment->phuongThuc === $data['phuongThuc'], 409, 'Đã có yêu cầu thanh toán bằng phương thức khác.');

                return $payment;
            }
            abort_if($payment && $payment->trangThai === 'THANH_CONG', 409);
            abort_unless($order->veGhes()->where('trangThai', 'GIU_CHO')->exists() || $order->chiTietComboDonHangs()->exists(), 422, 'Đơn hàng trống.');
            foreach ($order->chiTietComboDonHangs()->with('combo.chiTietCombos.sanPham')->get() as $line) {
                abort_unless($line->combo && $line->combo->trangThai === 'HOAT_DONG', 422, 'Combo đã ngừng bán.');
                foreach ($line->combo->chiTietCombos as $component) {
                    abort_unless($component->sanPham && $component->sanPham->trangThai === 'HOAT_DONG', 422, 'Sản phẩm đã ngừng bán.');
                }
            }
            $total = $order->tinhTongTien();
            $order->update(['tongTien' => $total]);
            $payment ??= $order->thanhToan()->make(['maTT' => 'TT'.Str::ulid()]);
            $payment->fill(['maGiaoDich' => 'GD'.Str::ulid(), 'phuongThuc' => $data['phuongThuc'], 'soTien' => $total, 'trangThai' => 'CHO_THANH_TOAN', 'ngayThanhToan' => null])->save();

            return $payment;
        }, 3);

        return response()->json(['data' => $payment], 201);
    }

    public function show(Request $request, string $maDonHang): JsonResponse
    {
        $customer = OrderAccess::customer($request);
        $order = DonHang::where('maKH', $customer->maKH)->findOrFail($maDonHang);

        return response()->json(['data' => $order->thanhToan()->firstOrFail()]);
    }

    public function confirm(Request $request, string $maTT): JsonResponse
    {
        $staff = OrderAccess::staff($request);
        abort_unless($staff->nhanVien && $staff->nhanVien->trangThai === 'DANG_LAM', 403, 'Hồ sơ nhân viên không hoạt động.');
        $request->validate(['maGiaoDich' => ['required', 'string'], 'soTien' => ['required', 'numeric', 'min:0', 'decimal:0,2']]);

        return $this->finish($request, $maTT, false, $staff->maNV);
    }

    public function simulate(Request $request, string $maTT): JsonResponse
    {
        $this->mockEnabled();
        OrderAccess::customer($request);
        $request->validate(['maGiaoDich' => ['required', 'string'], 'ketQua' => ['required', 'in:THANH_CONG,THAT_BAI']]);

        return $this->finish($request, $maTT, true);
    }

    private function finish(Request $request, string $id, bool $mock, ?string $staffId = null): JsonResponse
    {
        $candidate = ThanhToan::findOrFail($id);
        $result = DB::transaction(function () use ($request, $candidate, $mock, $staffId) {
            $order = $mock ? OrderAccess::owned($request, $candidate->maDonHang) : DonHang::whereKey($candidate->maDonHang)->lockForUpdate()->firstOrFail();
            $payment = $order->thanhToan()->lockForUpdate()->firstOrFail();
            abort_unless($payment->phuongThuc === ($mock ? 'GIA_LAP' : 'TIEN_MAT'), 409, 'Sai phương thức thanh toán.');
            abort_unless(hash_equals($payment->maGiaoDich, $request->string('maGiaoDich')->toString()), 409, 'Yêu cầu thanh toán đã thay đổi.');
            if (! $mock) {
                abort_unless((int) round((float) $request->soTien * 100) === (int) round((float) $payment->soTien * 100), 422, 'Số tiền xác nhận không khớp.');
            }
            $outcome = $mock ? $request->ketQua : 'THANH_CONG';
            if (in_array($payment->trangThai, ['THANH_CONG', 'THAT_BAI'], true)) {
                abort_unless($payment->trangThai === $outcome, 409, 'Kết quả thanh toán đã được ghi nhận.');

                return ['data' => $payment, 'status' => 200];
            }
            abort_unless($payment->trangThai === 'CHO_THANH_TOAN' && $order->trangThai === 'CHO_THANH_TOAN', 409, 'Đơn đã đóng.');
            if ($order->daHetHan()) {
                $order->huy('HET_HAN');

                return ['data' => $payment->fresh(), 'status' => 409, 'message' => 'Đơn đã hết hạn.'];
            }
            foreach ($order->veGhes()->where('trangThai', 'GIU_CHO')->with('lichChieu')->get() as $ticket) {
                $show = $ticket->lichChieu;
                $startsAt = $show->ngayChieu->copy()->setTimeFromTimeString($show->gioBatDau->format('H:i:s'));
                abort_unless($show->trangThai === 'HOAT_DONG' && $startsAt->isFuture(), 409, 'Suất chiếu đã bắt đầu hoặc ngừng hoạt động.');
            }
            $payment->update(['trangThai' => $outcome, 'ngayThanhToan' => $outcome === 'THANH_CONG' ? now() : null]);
            if ($outcome === 'THANH_CONG') {
                $order->veGhes()->where('trangThai', 'GIU_CHO')->update(['trangThai' => 'DA_DAT']);
                $order->update(['trangThai' => 'DA_THANH_TOAN', 'maNV' => $staffId, 'maQR' => 'QR'.Str::random(40)]);
            }

            return ['data' => $payment, 'status' => 200];
        }, 3);
        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
