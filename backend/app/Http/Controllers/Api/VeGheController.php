<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VeGhe;
use App\Models\Ghe;
use App\Models\LichChieu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VeGheController extends Controller
{
    public function index(Request $request)
    {
        $query = VeGhe::with([
            'donHang',
            'lichChieu.phim',
            'lichChieu.phongChieu',
            'ghe'
        ]);

        if ($request->filled('maLichChieu')) {
            $query->where('maLichChieu', $request->maLichChieu);
        }

        if ($request->filled('maGhe')) {
            $query->where('maGhe', $request->maGhe);
        }

        if ($request->filled('trangThai')) {
            $query->where('trangThai', $request->trangThai);
        }

        $veGhes = $query
            ->orderBy('maVe')
            ->paginate(20);

        return response()->json($veGhes);
    }

    public function show(string $maVe)
    {
        $veGhe = VeGhe::with([
            'donHang',
            'lichChieu.phim',
            'lichChieu.phongChieu',
            'ghe'
        ])->findOrFail($maVe);

        return response()->json([
            'message' => 'Lấy thông tin vé ghế thành công',
            'data' => $veGhe,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'maDonHang' => ['required', 'exists:don_hangs,maDonHang'],
            'maLichChieu' => ['required', 'exists:lich_chieus,maLichChieu'],
            'maGhe' => ['required', 'exists:ghes,maGhe'],
        ]);

        $result = DB::transaction(function () use ($data) {

            // Lấy lịch chiếu
            $lichChieu = LichChieu::findOrFail($data['maLichChieu']);

            // Lấy ghế được chọn
            $ghe = Ghe::findOrFail($data['maGhe']);

            // Kiểm tra ghế có thuộc sơ đồ của phòng chiếu không
            if ($ghe->soDoGhe->maPhong !== $lichChieu->maPhong) {
                abort(
                    422,
                    'Ghế không thuộc phòng chiếu của lịch chiếu này.'
                );
            }

            /*
             * ==========================================
             * XỬ LÝ GHẾ ĐÔI
             * ==========================================
             */
            if ($ghe->loaiGhe === 'DOI') {

                // Xác định ghế còn lại trong cặp
                $cot = (int) $ghe->cot;

                $cotCap = ($cot % 2 === 1)
                    ? $cot + 1
                    : $cot - 1;

                $gheCap = Ghe::where('maSoDo', $ghe->maSoDo)
                    ->where('hang', $ghe->hang)
                    ->where('cot', $cotCap)
                    ->where('loaiGhe', 'DOI')
                    ->first();

                if (!$gheCap) {
                    abort(
                        422,
                        'Không tìm thấy ghế còn lại của cặp ghế đôi.'
                    );
                }

                // Khóa cả 2 ghế để tránh 2 người đặt cùng lúc
                $gheList = Ghe::whereIn('maGhe', [
                    $ghe->maGhe,
                    $gheCap->maGhe
                ])
                    ->orderBy('maGhe')
                    ->lockForUpdate()
                    ->get();

                // Kiểm tra cả 2 ghế phải hoạt động
                foreach ($gheList as $seat) {
                    if ($seat->trangThai !== 'HOAT_DONG') {
                        abort(
                            422,
                            "Ghế {$seat->maGhe} hiện không hoạt động."
                        );
                    }
                }

                // Kiểm tra cả 2 ghế chưa được giữ/đặt
                $daDat = VeGhe::where('maLichChieu', $data['maLichChieu'])
                    ->whereIn('maGhe', [
                        $ghe->maGhe,
                        $gheCap->maGhe
                    ])
                    ->whereIn('trangThai', ['GIU_CHO', 'DA_DAT'])
                    ->exists();

                if ($daDat) {
                    abort(
                        422,
                        'Một hoặc cả hai ghế trong cặp đã được giữ hoặc đặt.'
                    );
                }

                // Giá của 1 ghế vật lý = giá VIP
                $giaMoiGhe = $lichChieu->tinhGiaVe('VIP');

                $veGhes = collect();

                // Tạo vé cho CẢ 2 ghế
                foreach ($gheList as $seat) {
                    $veGhes->push(
                        VeGhe::create([
                            'maDonHang' => $data['maDonHang'],
                            'maLichChieu' => $data['maLichChieu'],
                            'maGhe' => $seat->maGhe,
                            'giaVe' => $giaMoiGhe,
                            'trangThai' => 'GIU_CHO',
                            'ngayTao' => now(),
                        ])
                    );
                }

                return $veGhes;
            }

            /*
             * ==========================================
             * XỬ LÝ GHẾ THƯỜNG / VIP
             * ==========================================
             */

            $ghe = Ghe::where('maGhe', $ghe->maGhe)
                ->lockForUpdate()
                ->firstOrFail();

            // Kiểm tra trạng thái ghế
            if ($ghe->trangThai !== 'HOAT_DONG') {
                abort(422, 'Ghế hiện không hoạt động.');
            }

            // Kiểm tra ghế đã được giữ/đặt chưa
            $daDat = VeGhe::where('maLichChieu', $data['maLichChieu'])
                ->where('maGhe', $data['maGhe'])
                ->whereIn('trangThai', ['GIU_CHO', 'DA_DAT'])
                ->exists();

            if ($daDat) {
                abort(
                    422,
                    'Ghế đã được giữ hoặc đặt trong lịch chiếu này.'
                );
            }

            // Tính giá theo loại ghế
            $giaVe = $lichChieu->tinhGiaVe($ghe->loaiGhe);

            $veGhe = VeGhe::create([
                'maDonHang' => $data['maDonHang'],
                'maLichChieu' => $data['maLichChieu'],
                'maGhe' => $data['maGhe'],
                'giaVe' => $giaVe,
                'trangThai' => 'GIU_CHO',
                'ngayTao' => now(),
            ]);

            return collect([$veGhe]);
        });

        $result->load([
            'donHang',
            'lichChieu.phim',
            'lichChieu.phongChieu',
            'ghe'
        ]);

        return response()->json([
            'message' => $result->count() === 2
                ? 'Giữ cặp ghế đôi thành công'
                : 'Giữ ghế thành công',
            'data' => $result,
        ], 201);
    }

    public function update(Request $request, string $maVe)
    {
        $veGhe = VeGhe::findOrFail($maVe);

        $data = $request->validate([
            'trangThai' => [
                'required',
                Rule::in([
                    'TRONG',
                    'GIU_CHO',
                    'DA_DAT',
                    'DA_HUY'
                ])
            ],
        ]);

        DB::transaction(function () use ($veGhe, $data) {

            // Khóa vé hiện tại
            $veGhe->lockForUpdate();

            // Nếu là ghế đôi thì cập nhật cả cặp
            $ghe = $veGhe->ghe;

            if ($ghe->loaiGhe === 'DOI') {

                $cot = (int) $ghe->cot;

                $cotCap = ($cot % 2 === 1)
                    ? $cot + 1
                    : $cot - 1;

                $gheCap = Ghe::where('maSoDo', $ghe->maSoDo)
                    ->where('hang', $ghe->hang)
                    ->where('cot', $cotCap)
                    ->where('loaiGhe', 'DOI')
                    ->first();

                if (!$gheCap) {
                    abort(
                        422,
                        'Không tìm thấy ghế còn lại của cặp ghế đôi.'
                    );
                }

                $veCap = VeGhe::where('maLichChieu', $veGhe->maLichChieu)
                    ->where('maGhe', $gheCap->maGhe)
                    ->lockForUpdate()
                    ->first();

                if (!$veCap) {
                    abort(
                        422,
                        'Không tìm thấy vé của ghế còn lại trong cặp.'
                    );
                }

                // Cập nhật cả 2 vé
                $veGhe->update([
                    'trangThai' => $data['trangThai']
                ]);

                $veCap->update([
                    'trangThai' => $data['trangThai']
                ]);

                return;
            }

            // Ghế thường / VIP
            $veGhe->update([
                'trangThai' => $data['trangThai']
            ]);
        });

        return response()->json([
            'message' => 'Cập nhật trạng thái vé ghế thành công',
            'data' => VeGhe::with([
                'donHang',
                'lichChieu.phim',
                'lichChieu.phongChieu',
                'ghe'
            ])->findOrFail($maVe),
        ]);
    }
}