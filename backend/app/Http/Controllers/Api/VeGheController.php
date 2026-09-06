<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ghe;
use App\Models\LichChieu;
use App\Models\OrderAccess;
use App\Models\VeGhe;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VeGheController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = OrderAccess::customer($request);
        $query = VeGhe::with(['lichChieu.phim', 'ghe'])->whereHas('donHang', fn ($q) => $q->where('maKH', $customer->maKH));
        if ($request->filled('maLichChieu')) {
            $request->validate(['maLichChieu' => ['string', 'max:255']]);
            $query->where('maLichChieu', $request->maLichChieu);
        }

        return response()->json($query->orderBy('maVe')->paginate(20));
    }

    public function show(Request $request, string $maVe): JsonResponse
    {
        $customer = OrderAccess::customer($request);
        $ticket = VeGhe::with(['lichChieu.phim', 'ghe'])->whereHas('donHang', fn ($q) => $q->where('maKH', $customer->maKH))->findOrFail($maVe);

        return response()->json(['data' => $ticket]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'maDonHang' => ['required', 'string'], 'maLichChieu' => ['required', 'string'], 'maGhe' => ['required', 'string'],
        ]);
        $tickets = DB::transaction(function () use ($request, $data) {
            $order = OrderAccess::owned($request, $data['maDonHang']);
            OrderAccess::editable($order);
            $show = LichChieu::with('phongChieu')->findOrFail($data['maLichChieu']);
            $startsAt = $show->ngayChieu->copy()->setTimeFromTimeString($show->gioBatDau->format('H:i:s'));
            abort_unless($show->trangThai === 'HOAT_DONG' && $show->phongChieu->trangThai === 'HOAT_DONG' && $startsAt->isFuture(), 422, 'Suất chiếu không còn nhận đặt vé.');
            abort_if($order->veGhes()->whereIn('trangThai', ['GIU_CHO', 'DA_DAT'])->where('maLichChieu', '!=', $show->maLichChieu)->exists(), 422, 'Một đơn chỉ được đặt một suất chiếu.');
            $seat = Ghe::with('soDoGhe')->findOrFail($data['maGhe']);
            abort_unless($seat->soDoGhe && $seat->soDoGhe->maPhong === $show->maPhong, 422, 'Ghế không thuộc phòng chiếu.');
            $ids = [$seat->maGhe];
            if ($seat->loaiGhe === 'DOI') {
                $partner = Ghe::where('maSoDo', $seat->maSoDo)->where('hang', $seat->hang)
                    ->where('cot', $seat->cot % 2 === 1 ? $seat->cot + 1 : $seat->cot - 1)->where('loaiGhe', 'DOI')->first();
                abort_unless($partner, 422, 'Không tìm thấy ghế đôi còn lại.');
                $ids[] = $partner->maGhe;
            }
            $seats = Ghe::whereIn('maGhe', $ids)->orderBy('maGhe')->lockForUpdate()->get();
            foreach ($seats as $item) {
                abort_unless($item->trangThai === 'HOAT_DONG', 422, 'Ghế không hoạt động.');
            }
            abort_if(VeGhe::where('maLichChieu', $show->maLichChieu)->whereIn('maGhe', $ids)->whereIn('trangThai', ['GIU_CHO', 'DA_DAT'])->exists(), 409, 'Ghế đã được giữ hoặc đặt.');
            $tickets = new Collection;
            foreach ($seats as $item) {
                $tickets->push($order->veGhes()->create([
                    'maVe' => 'VE'.Str::ulid(), 'maLichChieu' => $show->maLichChieu, 'maGhe' => $item->maGhe,
                    'giaVe' => round($show->tinhGiaVe($item->loaiGhe === 'DOI' ? 'VIP' : $item->loaiGhe), 2),
                    'trangThai' => 'GIU_CHO', 'ngayTao' => now(),
                ]));
            }
            $order->update(['tongTien' => $order->tinhTongTien()]);

            return $tickets->load('ghe');
        }, 3);

        return response()->json(['data' => $tickets], 201);
    }

    public function update(Request $request, string $maVe): JsonResponse
    {
        $request->validate(['trangThai' => ['required', 'in:DA_HUY']]);
        $customer = OrderAccess::customer($request);
        $ticket = VeGhe::whereHas('donHang', fn ($q) => $q->where('maKH', $customer->maKH))->findOrFail($maVe);
        $order = DB::transaction(function () use ($request, $ticket) {
            $order = OrderAccess::owned($request, $ticket->maDonHang);
            OrderAccess::editable($order);
            $ticket = $order->veGhes()->findOrFail($ticket->maVe);
            abort_unless($ticket->trangThai === 'GIU_CHO', 409, 'Vé không còn giữ chỗ.');
            $seat = $ticket->ghe;
            $ids = [$seat->maGhe];
            if ($seat->loaiGhe === 'DOI') {
                $partner = Ghe::where('maSoDo', $seat->maSoDo)->where('hang', $seat->hang)
                    ->where('cot', $seat->cot % 2 === 1 ? $seat->cot + 1 : $seat->cot - 1)->where('loaiGhe', 'DOI')->firstOrFail();
                $ids[] = $partner->maGhe;
            }
            $order->veGhes()->where('maLichChieu', $ticket->maLichChieu)->whereIn('maGhe', $ids)->where('trangThai', 'GIU_CHO')->update(['trangThai' => 'DA_HUY']);
            $order->update(['tongTien' => $order->tinhTongTien()]);

            return $order->load('veGhes');
        }, 3);

        return response()->json(['data' => $order]);
    }
}
