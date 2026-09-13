<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LichChieu;
use App\Models\VeGhe;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LichChieuController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH CÔNG KHAI
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        $today = $now->toDateString();

        $currentTime = $now->format('H:i:s');

        $lichChieus = LichChieu::with([
            'phim',
            'phongChieu',
        ])
            ->where(
                'trangThai',
                'HOAT_DONG'
            )
            ->where(function ($query) use (
                $today,
                $currentTime
            ) {
                $query
                    ->whereDate(
                        'ngayChieu',
                        '>',
                        $today
                    )
                    ->orWhere(function ($q) use (
                        $today,
                        $currentTime
                    ) {
                        $q
                            ->whereDate(
                                'ngayChieu',
                                $today
                            )
                            ->whereTime(
                                'gioBatDau',
                                '>=',
                                $currentTime
                            );
                    });
            })
            ->orderBy('ngayChieu')
            ->orderBy('gioBatDau')
            ->paginate(10);

        return response()->json(
            $lichChieus
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH QUẢN LÝ
    |--------------------------------------------------------------------------
    */
    public function management(
        Request $request
    ) {
        $data = $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ngayChieu' => [
                'nullable',
                'date',
            ],

            'trangThai' => [
                'nullable',
                Rule::in([
                    'HOAT_DONG',
                    'NGUNG_HOAT_DONG',
                ]),
            ],

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ]);

        $query = LichChieu::with([
            'phim',
            'phongChieu',
        ]);

        if (!empty($data['q'])) {
            $keyword =
                trim($data['q']);

            $query->where(
                function ($q) use (
                    $keyword
                ) {
                    $q
                        ->where(
                            'maLichChieu',
                            'like',
                            "%{$keyword}%"
                        )
                        ->orWhereHas(
                            'phim',
                            function ($phim) use (
                                $keyword
                            ) {
                                $phim->where(
                                    'tenPhim',
                                    'like',
                                    "%{$keyword}%"
                                );
                            }
                        )
                        ->orWhereHas(
                            'phongChieu',
                            function ($phong) use (
                                $keyword
                            ) {
                                $phong->where(
                                    'tenPhong',
                                    'like',
                                    "%{$keyword}%"
                                );
                            }
                        );
                }
            );
        }

        if (!empty(
            $data['ngayChieu']
        )) {
            $query->whereDate(
                'ngayChieu',
                $data['ngayChieu']
            );
        }

        if (!empty(
            $data['trangThai']
        )) {
            $query->where(
                'trangThai',
                $data['trangThai']
            );
        }

        return response()->json(
            $query
                ->orderByDesc(
                    'ngayChieu'
                )
                ->orderBy(
                    'gioBatDau'
                )
                ->paginate(10)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | THÊM LỊCH CHIẾU
    |--------------------------------------------------------------------------
    */
    public function store(
        Request $request
    ) {
        $data = $request->validate([
            'maLichChieu' => [
                'required',
                'string',
                'max:50',
                'unique:lich_chieus,maLichChieu',
            ],

            'maPhim' => [
                'required',
                'exists:phims,maPhim',
            ],

            'maPhong' => [
                'required',
                'exists:phong_chieus,maPhong',
            ],

            'ngayChieu' => [
                'required',
                'date',
            ],

            'gioBatDau' => [
                'required',
                'date_format:H:i',
            ],

            'gioKetThuc' => [
                'required',
                'date_format:H:i',
                'after:gioBatDau',
            ],

            'giaVeCoBan' => [
                'required',
                'numeric',
                'min:0',
            ],
        ], [
            'maLichChieu.unique' =>
                'Mã lịch chiếu đã tồn tại.',

            'gioKetThuc.after' =>
                'Giờ kết thúc phải sau giờ bắt đầu.',
        ]);

        $this->kiemTraThoiGian(
            $data['ngayChieu'],
            $data['gioBatDau']
        );

        $this->kiemTraTrungLich(
            $data['maPhong'],
            $data['ngayChieu'],
            $data['gioBatDau'],
            $data['gioKetThuc']
        );

        $data['trangThai'] =
            'HOAT_DONG';

        $lichChieu =
            LichChieu::create(
                $data
            );

        return response()->json([
            'message' =>
                'Thêm lịch chiếu thành công.',

            'data' =>
                $lichChieu->load([
                    'phim',
                    'phongChieu',
                ]),
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | CHI TIẾT LỊCH CHIẾU
    |--------------------------------------------------------------------------
    */
    public function show(
        string $maLichChieu
    ) {
        $lichChieu =
            LichChieu::with([
                'phim',
                'phongChieu',
            ])
                ->findOrFail(
                    $maLichChieu
                );

        return response()->json([
            'message' =>
                'Lấy thông tin lịch chiếu thành công.',

            'data' =>
                $lichChieu,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CẬP NHẬT LỊCH CHIẾU
    |--------------------------------------------------------------------------
    |
    | Theo Word:
    | - Chỉ cập nhật khi chưa có khách đặt vé.
    | - Không được trùng lịch trong cùng phòng.
    |
    */
    public function update(
        Request $request,
        string $maLichChieu
    ) {
        $lichChieu =
            LichChieu::findOrFail(
                $maLichChieu
            );

        if (
            $this->daCoKhachDatVe(
                $maLichChieu
            )
        ) {
            return response()->json([
                'message' =>
                    'Không thể cập nhật vì lịch chiếu đã có khách hàng đặt vé.',
            ], 409);
        }

        $data = $request->validate([
            'maPhim' => [
                'required',
                'exists:phims,maPhim',
            ],

            'maPhong' => [
                'required',
                'exists:phong_chieus,maPhong',
            ],

            'ngayChieu' => [
                'required',
                'date',
            ],

            'gioBatDau' => [
                'required',
                'date_format:H:i',
            ],

            'gioKetThuc' => [
                'required',
                'date_format:H:i',
                'after:gioBatDau',
            ],

            'giaVeCoBan' => [
                'required',
                'numeric',
                'min:0',
            ],
        ], [
            'gioKetThuc.after' =>
                'Giờ kết thúc phải sau giờ bắt đầu.',
        ]);

        $this->kiemTraThoiGian(
            $data['ngayChieu'],
            $data['gioBatDau']
        );

        $this->kiemTraTrungLich(
            $data['maPhong'],
            $data['ngayChieu'],
            $data['gioBatDau'],
            $data['gioKetThuc'],
            $maLichChieu
        );

        $lichChieu->update(
            $data
        );

        return response()->json([
            'message' =>
                'Cập nhật lịch chiếu thành công.',

            'data' =>
                $lichChieu
                    ->fresh()
                    ->load([
                        'phim',
                        'phongChieu',
                    ]),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | HỦY LỊCH CHIẾU
    |--------------------------------------------------------------------------
    |
    | Theo Word:
    | - Nếu đã có khách đặt vé -> chặn.
    | - Nếu chưa có vé -> cho phép xác nhận hủy.
    |
    */
    public function destroy(
        Request $request,
        string $maLichChieu
    ) {
        $lichChieu =
            LichChieu::findOrFail(
                $maLichChieu
            );

        if (
            $this->daCoKhachDatVe(
                $maLichChieu
            )
        ) {
            return response()->json([
                'message' =>
                    'Không thể hủy do đã có vé được bán.',
            ], 409);
        }

        /*
         * Word cho phép:
         * - Xóa lịch
         * hoặc
         * - Chuyển trạng thái hủy.
         *
         * Database hiện tại chỉ có:
         * HOAT_DONG / NGUNG_HOAT_DONG
         *
         * Vì vậy giữ bản ghi và chuyển sang
         * NGUNG_HOAT_DONG để an toàn dữ liệu.
         */
        $lichChieu->update([
            'trangThai' =>
                'NGUNG_HOAT_DONG',
        ]);

        return response()->json([
            'message' =>
                'Hủy lịch chiếu thành công.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA TRÙNG LỊCH
    |--------------------------------------------------------------------------
    */
    private function kiemTraTrungLich(
        string $maPhong,
        string $ngayChieu,
        string $gioBatDau,
        string $gioKetThuc,
        ?string $ignoreId = null
    ): void {
        $query =
            LichChieu::where(
                'maPhong',
                $maPhong
            )
                ->whereDate(
                    'ngayChieu',
                    $ngayChieu
                )
                ->where(
                    'trangThai',
                    'HOAT_DONG'
                )
                ->where(
                    'gioBatDau',
                    '<',
                    $gioKetThuc
                )
                ->where(
                    'gioKetThuc',
                    '>',
                    $gioBatDau
                );

        if ($ignoreId) {
            $query->where(
                'maLichChieu',
                '!=',
                $ignoreId
            );
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'gioBatDau' =>
                    'Phòng chiếu đã có lịch trong khoảng thời gian này.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA THỜI GIAN HỢP LỆ
    |--------------------------------------------------------------------------
    */
    private function kiemTraThoiGian(
        string $ngayChieu,
        string $gioBatDau
    ): void {
        $batDau =
            Carbon::createFromFormat(
                'Y-m-d H:i',
                $ngayChieu
                . ' '
                . $gioBatDau,
                'Asia/Ho_Chi_Minh'
            );

        $now =
            Carbon::now(
                'Asia/Ho_Chi_Minh'
            );

        if (
            $batDau->lessThanOrEqualTo(
                $now
            )
        ) {
            throw ValidationException::withMessages([
                'ngayChieu' =>
                    'Thời gian chiếu phải lớn hơn thời gian hiện tại.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA ĐÃ CÓ KHÁCH ĐẶT VÉ
    |--------------------------------------------------------------------------
    */
    private function daCoKhachDatVe(
        string $maLichChieu
    ): bool {
        return VeGhe::where(
            'maLichChieu',
            $maLichChieu
        )
            ->whereIn(
                'trangThai',
                [
                    'GIU_CHO',
                    'DA_DAT',
                    'DA_SU_DUNG',
                ]
            )
            ->whereHas(
                'donHang',
                function ($query) {
                    $query->where(
                        'trangThai',
                        '!=',
                        'DA_HUY'
                    );
                }
            )
            ->exists();
    }
}