<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\OrderAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DonHangController extends Controller
{
    public function management(Request $request): JsonResponse
    {
        OrderAccess::staff($request);
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'trangThai' => ['nullable', 'in:DA_THANH_TOAN,DA_SU_DUNG,DA_HUY,CHO_THANH_TOAN'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $query = DonHang::with([
            'khachHang',
            'veGhes.lichChieu.phim',
            'veGhes.ghe',
        ])->withCount('veGhes')->orderByDesc('ngayDat');

        if (! empty($data['trangThai'])) {
            $query->where('trangThai', $data['trangThai']);
        }

        if (($search = trim($data['q'] ?? '')) !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('maDonHang', 'like', "%{$search}%")
                    ->orWhereHas('khachHang', function ($customerQuery) use ($search): void {
                        $customerQuery->where('hoTen', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return response()->json($query->paginate(10));
    }

    public function managementShow(Request $request, string $maDonHang): JsonResponse
    {
        OrderAccess::staff($request);

        $order = DonHang::with($this->managementRelations())
            ->findOrFail($maDonHang);

        return response()->json([
            'message' => 'Lấy chi tiết đơn hàng thành công.',
            'data' => $order,
        ]);
    }

    public function previewScan(Request $request): JsonResponse
    {
        OrderAccess::staff($request);
        $data = $request->validate([
            'qrData' => ['required', 'string', 'max:5000'],
        ]);

        $qrData = trim($data['qrData']);
        $orderCode = $this->extractOrderCode($qrData);
        $qrCode = $this->extractQrCode($qrData);

        abort_unless($orderCode || $qrCode, 422, 'Mã QR không chứa mã đơn hàng hợp lệ.');

        $order = DonHang::with($this->managementRelations())
            ->when($orderCode, fn ($query) => $query->where('maDonHang', $orderCode), fn ($query) => $query->where('maQR', $qrCode))
            ->firstOrFail();

        return response()->json([
            'message' => 'Đã tìm thấy đơn hàng.',
            'data' => $order,
        ]);
    }

    public function scan(Request $request, string $maDonHang = ''): JsonResponse
    {
        $staff = OrderAccess::staff($request);
        $data = $request->validate([
            'qrData' => ['nullable', 'string', 'max:5000'],
            'maQR' => ['nullable', 'string', 'max:255'],
        ]);

        $qrData = trim($data['qrData'] ?? $data['maQR'] ?? '');
        $orderCode = $maDonHang ?: $this->extractOrderCode($qrData);
        $qrCode = $this->extractQrCode($qrData);

        abort_unless($orderCode || $qrCode, 422, 'Mã QR không chứa mã đơn hàng hợp lệ.');

        $order = DB::transaction(function () use ($orderCode, $qrCode, $staff): DonHang {
            $query = DonHang::with($this->managementRelations())
                ->lockForUpdate();
            if ($orderCode) {
                $query->where('maDonHang', $orderCode);
            } else {
                $query->where('maQR', $qrCode);
            }

            $order = $query->firstOrFail();
            abort_unless($order->trangThai === 'DA_THANH_TOAN', 409, $order->trangThai === 'DA_SU_DUNG'
                ? 'Vé của đơn hàng này đã được sử dụng.'
                : 'Đơn hàng chưa sẵn sàng để in vé.');
            abort_unless($order->veGhes()->where('trangThai', 'DA_DAT')->exists(), 409, 'Đơn hàng không còn vé hợp lệ.');

            $order->veGhes()->where('trangThai', 'DA_DAT')->update(['trangThai' => 'DA_SU_DUNG']);
            $order->update(['trangThai' => 'DA_SU_DUNG', 'maNV' => $staff->maNV]);

            return $order->fresh($this->managementRelations());
        }, 3);

        return response()->json([
            'message' => 'Quét mã thành công. Vé đã được đánh dấu đã sử dụng.',
            'data' => $order,
        ]);
    }

    private function extractOrderCode(string $value): ?string
    {
        if (preg_match('/(?:^|[|&\s])order=([^|&\s]+)/i', $value, $matches)) {
            return $matches[1];
        }

        return str_starts_with($value, 'DH') ? $value : null;
    }

    private function extractQrCode(string $value): ?string
    {
        if (preg_match('/(?:^|[|&\s])ticket=([^|&\s]+)/i', $value, $matches)) {
            return $matches[1];
        }

        return str_starts_with($value, 'QR') ? $value : null;
    }

    /** @return array<int, string> */
    private function managementRelations(): array
    {
        return [
            'khachHang',
            'veGhes.lichChieu.phim',
            'veGhes.lichChieu.phongChieu',
            'veGhes.ghe',
            'chiTietComboDonHangs.combo',
            'khuyenMai',
            'thanhToan',
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $taiKhoan = $request->user();

        if (
            $taiKhoan->vaiTro !== 'KHACH_HANG' ||
            $taiKhoan->trangThai !== 'HOAT_DONG'
        ) {
            return response()->json([
                'message' => 'Bạn không có quyền xem đơn hàng.',
            ], 403);
        }

        $khachHang = $taiKhoan->khachHang;

        if (
            ! $khachHang ||
            $khachHang->trangThai !== 'HOAT_DONG'
        ) {
            return response()->json([
                'message' => 'Thông tin khách hàng không hợp lệ hoặc đã bị khóa.',
            ], 403);
        }

        $donHangs = DonHang::where('maKH', $khachHang->maKH)
            ->orderByDesc('ngayDat')
            ->orderByDesc('maDonHang')
            ->paginate(10);

        return response()->json($donHangs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Lấy tài khoản từ token đăng nhập
        $taiKhoan = $request->user();

        // 2. Chỉ cho phép khách hàng đang hoạt động tạo đơn online
        if (
            $taiKhoan->vaiTro !== 'KHACH_HANG' ||
            $taiKhoan->trangThai !== 'HOAT_DONG'
        ) {
            return response()->json([
                'message' => 'Bạn không có quyền tạo đơn hàng.',
            ], 403);
        }

        // 3. Lấy hồ sơ khách hàng liên kết với tài khoản
        $khachHang = $taiKhoan->khachHang;

        if (
            ! $khachHang ||
            $khachHang->trangThai !== 'HOAT_DONG'
        ) {
            return response()->json([
                'message' => 'Thông tin khách hàng không hợp lệ hoặc đã bị khóa.',
            ], 403);
        }

        // 4. Tạo đơn hàng ban đầu, chưa có ghế hoặc combo
        $donHang = DonHang::create([
            'maDonHang' => 'DH'.Str::ulid(),
            'maKH' => $khachHang->maKH,
            'maNV' => null,
            'maKM' => null,
            'kieuDat' => 'ONLINE',
            'ngayDat' => now(),
            'tongTien' => 0,
            'maQR' => null,
            'trangThai' => 'CHO_THANH_TOAN',
        ]);

        // 5. Trả đơn vừa tạo cho frontend
        return response()->json([
            'message' => 'Tạo đơn hàng thành công.',
            'data' => $donHang,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $maDonHang)
    {
        $taiKhoan = $request->user();

        if (
            $taiKhoan->vaiTro !== 'KHACH_HANG' ||
            $taiKhoan->trangThai !== 'HOAT_DONG'
        ) {
            return response()->json([
                'message' => 'Bạn không có quyền xem đơn hàng.',
            ], 403);
        }

        $khachHang = $taiKhoan->khachHang;

        if (
            ! $khachHang ||
            $khachHang->trangThai !== 'HOAT_DONG'
        ) {
            return response()->json([
                'message' => 'Thông tin khách hàng không hợp lệ hoặc đã bị khóa.',
            ], 403);
        }

        $donHang = DonHang::with([
            'veGhes.ghe',
            'veGhes.lichChieu.phim',
            'veGhes.lichChieu.phongChieu',
            'chiTietComboDonHangs',
            'khuyenMai',
            'thanhToan',
        ])
            ->where('maKH', $khachHang->maKH)
            ->where('maDonHang', $maDonHang)
            ->firstOrFail();

        return response()->json([
            'message' => 'Lấy chi tiết đơn hàng thành công.',
            'data' => $donHang,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
