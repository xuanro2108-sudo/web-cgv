<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TicketConfirmationMail;
use App\Models\DonHang;
use App\Models\OrderAccess;
use App\Models\ThanhToan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
        $data = $request->validate(['phuongThuc' => ['required', 'in:TIEN_MAT,SEPAY_QR,GIA_LAP']]);
        if ($data['phuongThuc'] === 'GIA_LAP') {
            $this->mockEnabled();
        }
        if ($data['phuongThuc'] === 'SEPAY_QR') {
            abort_unless(
                config('payments.sepay.bank') && config('payments.sepay.account_number'),
                503,
                'Thanh toán QR chưa được cấu hình.'
            );
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

        return response()->json(['data' => $this->paymentPayload($payment)], 201);
    }

    public function show(Request $request, string $maDonHang): JsonResponse
    {
        $customer = OrderAccess::customer($request);
        $order = DonHang::where('maKH', $customer->maKH)->findOrFail($maDonHang);

        return response()->json(['data' => $this->paymentPayload($order->thanhToan()->firstOrFail())]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $configuredKey = (string) config('payments.sepay.webhook_key');
        $authorization = (string) $request->header('Authorization');
        $providedKey = (string) ($request->header('X-SePay-API-Key') ?: $request->bearerToken());
        if ($providedKey === '' && str_starts_with(strtolower($authorization), 'apikey ')) {
            $providedKey = trim(substr($authorization, 7));
        }
        abort_unless($configuredKey !== '' && hash_equals($configuredKey, $providedKey), 401, 'Webhook không hợp lệ.');

        $data = $request->validate([
            'transferType' => ['nullable', 'string'],
            'transferAmount' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'content' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'transactionContent' => ['nullable', 'string'],
        ]);
        abort_unless(strtolower((string) ($data['transferType'] ?? 'in')) === 'in', 422, 'Chỉ chấp nhận giao dịch tiền vào.');

        $content = implode(' ', array_filter([
            $data['content'] ?? null,
            $data['description'] ?? null,
            $data['transactionContent'] ?? null,
        ]));
        $amount = (float) ($data['transferAmount'] ?? $data['amount'] ?? 0);
        $payment = ThanhToan::whereIn('phuongThuc', ['SEPAY_QR', 'CHUYEN_KHOAN'])
            ->whereIn('trangThai', ['CHO_THANH_TOAN', 'THANH_CONG'])
            ->with(['donHang.khachHang'])
            ->get()
            ->first(function (ThanhToan $candidate) use ($content, $amount) {
                // Match by exact amount first if provided
                $candidateAmount = (int) round((float) $candidate->soTien * 100);
                $incomingAmount = (int) round($amount * 100);
                if ($incomingAmount > 0 && $candidateAmount !== $incomingAmount) {
                    return false;
                }
                // Match by maGiaoDich
                if ($candidate->maGiaoDich && Str::contains($content, $candidate->maGiaoDich)) {
                    return true;
                }
                // Match by maDonHang
                if ($candidate->maDonHang && Str::contains($content, $candidate->maDonHang)) {
                    return true;
                }
                // Match by customer phone number
                $phone = $candidate->donHang?->khachHang?->soDienThoai;
                $digits = $phone ? preg_replace('/\D/', '', $phone) : '';
                if ($digits !== '' && Str::contains($content, $digits)) {
                    return true;
                }
                // Fallback: if amount matches and payment exists in last 30 minutes
                return $candidateAmount === $incomingAmount;
            });

        if (! $payment) {
            return response()->json([
                'success' => true,
                'message' => 'Đã nhận Webhook SePay thành công.',
            ]);
        }

        if ($payment->trangThai === 'THANH_CONG') {
            if (! $payment->emailDaGui) {
                $this->sendTicketEmail($payment);
            }

            return response()->json(['success' => true, 'data' => $payment]);
        }

        $result = DB::transaction(function () use ($payment) {
            $order = DonHang::whereKey($payment->maDonHang)->lockForUpdate()->firstOrFail();
            $lockedPayment = $order->thanhToan()->lockForUpdate()->firstOrFail();
            abort_unless($lockedPayment->trangThai === 'CHO_THANH_TOAN', 409, 'Thanh toán đã được xử lý.');
            abort_unless($order->trangThai === 'CHO_THANH_TOAN' && ! $order->daHetHan(), 409, 'Đơn hàng không còn hiệu lực.');

            $lockedPayment->update(['trangThai' => 'THANH_CONG', 'ngayThanhToan' => now()]);
            $order->veGhes()->where('trangThai', 'GIU_CHO')->update(['trangThai' => 'DA_DAT']);
            $order->update(['trangThai' => 'DA_THANH_TOAN', 'maQR' => 'QR'.Str::random(40)]);

            return $lockedPayment->fresh();
        }, 3);

        if (! $result->emailDaGui) {
            $this->sendTicketEmail($result);
        }
        return response()->json(['success' => true, 'data' => $result]);
    }

    public function confirm(Request $request, string $maTT): JsonResponse
    {
        $staff = OrderAccess::staff($request);
        if ($staff->nhanVien) {
            abort_unless($staff->nhanVien->trangThai === 'DANG_LAM', 403, 'Hồ sơ nhân viên không hoạt động.');
        } else {
            abort_unless($staff->trangThai === 'HOAT_DONG', 403, 'Tài khoản không hoạt động.');
        }
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
            if ($mock) {
                abort_unless($payment->phuongThuc === 'GIA_LAP', 409, 'Sai phương thức thanh toán.');
            }
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

        if (($result['data']->trangThai ?? null) === 'THANH_CONG' && ! $result['data']->emailDaGui) {
            $this->sendTicketEmail($result['data']);
        }

        return response()->json($result, $status);
    }

    private function sendTicketEmail(ThanhToan $payment): void
    {
        $order = DonHang::with([
            'khachHang',
            'veGhes.ghe',
            'veGhes.lichChieu.phim',
            'veGhes.lichChieu.phongChieu',
        ])->findOrFail($payment->maDonHang);
        $email = $order->khachHang?->email;

        abort_unless($email, 422, 'Khách hàng chưa có email để nhận vé.');

        $ticketData = implode('|', [
            'CGV-TICKET',
            'ticket='.$order->maQR,
            'order='.$order->maDonHang,
            'movie='.$order->veGhes->first()?->lichChieu?->phim?->tenPhim,
            'seats='.$order->veGhes
                ->map(fn ($ticket) => ($ticket->ghe?->hang ?? '').($ticket->ghe?->cot ?? ''))
                ->filter()
                ->join(','),
        ]);
        $ticketQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&format=png&data='
            .rawurlencode($ticketData);

        Mail::to($email)->send(new TicketConfirmationMail($order, $payment, $ticketQrUrl));
        $payment->update(['emailDaGui' => true]);
    }

    private function paymentPayload(ThanhToan $payment): array
    {
        $payload = $payment->toArray();
        if ($payment->phuongThuc === 'SEPAY_QR') {
            $payload['qrUrl'] = sprintf(
                'https://img.vietqr.io/image/%s-%s-%s.png?amount=%s&addInfo=%s&accountName=%s',
                rawurlencode((string) config('payments.sepay.bank')),
                rawurlencode((string) config('payments.sepay.account_number')),
                rawurlencode((string) config('payments.sepay.template', 'compact2')),
                rawurlencode((string) $payment->soTien),
                rawurlencode($payment->maGiaoDich),
                rawurlencode((string) config('payments.sepay.account_name'))
            );
            $payload['transferContent'] = $payment->maGiaoDich;
            $payload['bank'] = config('payments.sepay.bank');
            $payload['accountNumber'] = config('payments.sepay.account_number');
            $payload['accountName'] = config('payments.sepay.account_name');
        }

        return $payload;
    }
}
