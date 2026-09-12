<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\Ghe;
use App\Models\KhachHang;
use App\Models\LichChieu;
use App\Models\OrderAccess;
use App\Models\ThanhToan;
use App\Models\VeGhe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BanVeTaiQuayController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $staff = OrderAccess::staff($request);
        if ($staff->nhanVien) {
            abort_unless($staff->nhanVien->trangThai === 'DANG_LAM', 403, 'Hồ sơ nhân viên không hoạt động.');
        } else {
            abort_unless($staff->trangThai === 'HOAT_DONG', 403, 'Tài khoản không hoạt động.');
        }

        $data = $request->validate([
            'hoTen' => ['required', 'string', 'max:255'],
            'soDienThoai' => ['required', 'string', 'max:20'],
            'maLichChieu' => ['required', 'string', 'exists:lich_chieus,maLichChieu'],
            'maGhes' => ['required', 'array', 'min:1'],
            'maGhes.*' => ['required', 'string', 'distinct', 'exists:ghes,maGhe'],
            'combos' => ['nullable', 'array'],
            'combos.*.maCombo' => ['required', 'string', 'exists:combos,maCombo'],
            'combos.*.soLuong' => ['required', 'integer', 'min:1', 'max:100'],
            'phuongThuc' => ['nullable', 'string', 'in:TIEN_MAT,SEPAY_QR,CHUYEN_KHOAN,ATM,MOMO,ZALOPAY'],
            'maGiaoDich' => ['nullable', 'string', 'max:255'],
            'xacNhanNgay' => ['nullable', 'boolean'],
        ]);

        $order = DB::transaction(function () use ($data, $staff): DonHang {
            $customer = KhachHang::where('soDienThoai', $data['soDienThoai'])->lockForUpdate()->first();
            if (! $customer) {
                $id = (string) Str::ulid();
                $customer = KhachHang::create([
                    'maKH' => 'KH'.$id,
                    'hoTen' => $data['hoTen'],
                    'soDienThoai' => $data['soDienThoai'],
                    'email' => 'taiquay-'.$id.'@cgv.local',
                    'ngayDangKy' => today(),
                    'trangThai' => 'HOAT_DONG',
                ]);
            }

            abort_unless($customer->trangThai === 'HOAT_DONG', 422, 'Khách hàng đang bị khóa.');
            $showtime = LichChieu::with('phongChieu')->findOrFail($data['maLichChieu']);
            $startsAt = $showtime->ngayChieu->copy()->setTimeFromTimeString($showtime->gioBatDau->format('H:i:s'));
            abort_unless($showtime->trangThai === 'HOAT_DONG' && $showtime->phongChieu?->trangThai === 'HOAT_DONG' && $startsAt->isFuture(), 422, 'Suất chiếu không còn nhận đặt vé.');

            $seatIds = $this->seatIds($data['maGhes']);
            $seats = Ghe::with('soDoGhe')->whereIn('maGhe', $seatIds)->lockForUpdate()->get();
            abort_unless($seats->count() === count($seatIds), 422, 'Không tìm thấy ghế.');
            foreach ($seats as $seat) {
                abort_unless($seat->trangThai === 'HOAT_DONG' && $seat->soDoGhe?->maPhong === $showtime->maPhong, 422, 'Ghế không thuộc phòng chiếu hoặc đang ngưng hoạt động.');
            }

            VeGhe::where('maLichChieu', $showtime->maLichChieu)
                ->whereIn('maGhe', $seatIds)
                ->where('trangThai', 'GIU_CHO')
                ->where(function ($query) {
                    $query->whereHas('donHang', function ($dh) {
                        $dh->where('trangThai', '!=', 'CHO_THANH_TOAN')
                           ->orWhere(function ($expiry) {
                               $expiry->where('hetHanLuc', '<=', now())
                                      ->orWhere(function ($fallback) {
                                          $fallback->whereNull('hetHanLuc')
                                                   ->where('ngayDat', '<=', now()->subMinutes(10));
                                      });
                           });
                    })->orWhereDoesntHave('donHang');
                })
                ->update(['trangThai' => 'DA_HUY']);

            abort_if(
                VeGhe::where('maLichChieu', $showtime->maLichChieu)
                    ->whereIn('maGhe', $seatIds)
                    ->whereIn('trangThai', ['GIU_CHO', 'DA_DAT', 'DA_SU_DUNG'])
                    ->exists(),
                409,
                'Một hoặc nhiều ghế vừa được giữ/đặt. Vui lòng chọn lại.'
            );

            $phuongThuc = $data['phuongThuc'] ?? 'TIEN_MAT';
            $isConfirmed = isset($data['xacNhanNgay']) ? (bool) $data['xacNhanNgay'] : ($phuongThuc === 'TIEN_MAT');
            $orderStatus = $isConfirmed ? 'DA_THANH_TOAN' : 'CHO_THANH_TOAN';
            $ticketStatus = $isConfirmed ? 'DA_DAT' : 'GIU_CHO';
            $paymentStatus = $isConfirmed ? 'THANH_CONG' : 'CHO_THANH_TOAN';

            $order = DonHang::create([
                'maDonHang' => 'DH'.Str::ulid(),
                'maKH' => $customer->maKH,
                'maNV' => $staff->maNV,
                'kieuDat' => 'TAI_QUAY',
                'ngayDat' => now(),
                'tongTien' => 0,
                'maQR' => 'QR'.Str::random(40),
                'trangThai' => $orderStatus,
                'hetHanLuc' => $isConfirmed ? null : now()->addMinutes(10),
            ]);

            $total = 0;
            foreach ($seats as $seat) {
                $price = round($showtime->tinhGiaVe($seat->loaiGhe === 'DOI' ? 'VIP' : $seat->loaiGhe), 2);
                $total += $price;
                VeGhe::create([
                    'maVe' => 'VE'.Str::ulid(),
                    'maDonHang' => $order->maDonHang,
                    'maLichChieu' => $showtime->maLichChieu,
                    'maGhe' => $seat->maGhe,
                    'giaVe' => $price,
                    'trangThai' => $ticketStatus,
                    'ngayTao' => now(),
                ]);
            }

            if (! empty($data['combos'])) {
                foreach ($data['combos'] as $comboItem) {
                    $combo = \App\Models\Combo::findOrFail($comboItem['maCombo']);
                    $qty = (int) $comboItem['soLuong'];
                    $sub = round($combo->donGia * $qty, 2);
                    $total += $sub;
                    \App\Models\ChiTietComboDonHang::create([
                        'maChiTiet' => 'CT'.Str::ulid(),
                        'maDonHang' => $order->maDonHang,
                        'maCombo' => $combo->maCombo,
                        'soLuong' => $qty,
                        'donGia' => $combo->donGia,
                        'thanhTien' => $sub,
                    ]);
                }
            }

            $order->update(['tongTien' => $total]);
            $transactionCode = $data['maGiaoDich'] ?? ('CGVPOS' . preg_replace('/\D/', '', $data['soDienThoai']));
            ThanhToan::create([
                'maTT' => 'TT'.Str::ulid(),
                'maDonHang' => $order->maDonHang,
                'maGiaoDich' => $transactionCode,
                'soTien' => $total,
                'phuongThuc' => $phuongThuc === 'CHUYEN_KHOAN' ? 'SEPAY_QR' : $phuongThuc,
                'ngayThanhToan' => $isConfirmed ? now() : null,
                'trangThai' => $paymentStatus,
            ]);

            return $order->fresh(['khachHang', 'veGhes.ghe', 'veGhes.lichChieu.phim', 'veGhes.lichChieu.phongChieu', 'chiTietComboDonHangs.combo', 'thanhToan']);
        }, 3);

        return response()->json(['message' => 'Bán vé tại quầy thành công.', 'data' => $order], 201);
    }

    /** @param array<int, string> $requestedIds
     *  @return array<int, string> */
    private function seatIds(array $requestedIds): array
    {
        $seats = Ghe::whereIn('maGhe', $requestedIds)->get();
        $ids = $seats->pluck('maGhe')->all();
        foreach ($seats->where('loaiGhe', 'DOI') as $seat) {
            $partner = Ghe::where('maSoDo', $seat->maSoDo)->where('hang', $seat->hang)
                ->where('cot', $seat->cot % 2 === 1 ? $seat->cot + 1 : $seat->cot - 1)->where('loaiGhe', 'DOI')->first();
            abort_unless($partner, 422, 'Không tìm thấy ghế đôi đi kèm.');
            $ids[] = $partner->maGhe;
        }

        return array_values(array_unique($ids));
    }
}
