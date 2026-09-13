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
    | DANH SÁCH LỊCH CHIẾU CÔNG KHAI
    |--------------------------------------------------------------------------
    |
    */
    public function index()
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        $lichChieus = LichChieu::with([
            'phim',
            'phongChieu.soDoGhe.ghes',
        ])
            ->where('trangThai', 'HOAT_DONG')
            ->where(function ($query) use ($today, $currentTime) {
                $query
                    ->whereDate('ngayChieu', '>', $today)
                    ->orWhere(function ($q) use ($today, $currentTime) {
                        $q
                            ->whereDate('ngayChieu', $today)
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

        foreach ($lichChieus->items() as $lichChieu) {
            $ghes = $lichChieu
                ->phongChieu
                ?->soDoGhe
                ?->ghes ?? collect();

            /*
             * Chỉ ghế vật lý HOAT_DONG mới có thể bán.
             * Ghế KHOA do hỏng không tính là ghế trống.
             */
            $maGheHoatDong = $ghes
                ->where('trangThai', 'HOAT_DONG')
                ->pluck('maGhe')
                ->values();

            $tongGheCoTheBan = $maGheHoatDong->count();

            $soGheDangBiChiem = 0;

            if ($maGheHoatDong->isNotEmpty()) {
                $soGheDangBiChiem = $this
                    ->queryVeDangChiemGhe(
                        $lichChieu->maLichChieu
                    )
                    ->whereIn(
                        'maGhe',
                        $maGheHoatDong->all()
                    )
                    ->distinct()
                    ->count('maGhe');
            }

            $lichChieu->setAttribute(
                'gheTrong',
                max(
                    $tongGheCoTheBan - $soGheDangBiChiem,
                    0
                )
            );
        }

        return response()->json($lichChieus);
    }

    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH LỊCH CHIẾU CHO QUẢN LÝ
    |--------------------------------------------------------------------------
    */
    public function management(Request $request)
    {
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
            $keyword = trim($data['q']);

            $query->where(function ($q) use ($keyword) {
                $q
                    ->where(
                        'maLichChieu',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhereHas(
                        'phim',
                        function ($phim) use ($keyword) {
                            $phim->where(
                                'tenPhim',
                                'like',
                                "%{$keyword}%"
                            );
                        }
                    )
                    ->orWhereHas(
                        'phongChieu',
                        function ($phong) use ($keyword) {
                            $phong->where(
                                'tenPhong',
                                'like',
                                "%{$keyword}%"
                            );
                        }
                    );
            });
        }

        if (!empty($data['ngayChieu'])) {
            $query->whereDate(
                'ngayChieu',
                $data['ngayChieu']
            );
        }

        if (!empty($data['trangThai'])) {
            $query->where(
                'trangThai',
                $data['trangThai']
            );
        }

        return response()->json(
            $query
                ->orderByDesc('ngayChieu')
                ->orderBy('gioBatDau')
                ->paginate(10)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | THÊM LỊCH CHIẾU
    |--------------------------------------------------------------------------
    |
    | Theo tài liệu:
    | - Chọn phim, phòng, ngày/giờ.
    | - Không được trùng lịch trong cùng phòng.
    | - Thời gian chiếu phải hợp lệ.
    |
    */
    public function store(Request $request)
    {
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

        $data['trangThai'] = 'HOAT_DONG';

        $lichChieu = LichChieu::create($data);

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
    | CHI TIẾT LỊCH CHIẾU + SƠ ĐỒ GHẾ
    |--------------------------------------------------------------------------
    |
    */
    public function show(string $maLichChieu)
    {
        $lichChieu = LichChieu::with([
            'phim',
            'phongChieu.soDoGhe.ghes',
        ])->findOrFail($maLichChieu);

        $veTheoGhe = $this
            ->queryVeDangChiemGhe($maLichChieu)
            ->get()
            ->keyBy('maGhe');

        $ghes = $lichChieu
            ->phongChieu
            ?->soDoGhe
            ?->ghes;

        $tongGheCoTheBan = 0;
        $soGheDangBiChiem = 0;

        if ($ghes) {
            foreach ($ghes as $ghe) {
                /*
                 * Ghế vật lý bị khóa do hỏng:
                 * không cho bán và giữ nguyên KHOA.
                 */
                if ($ghe->trangThai === 'KHOA') {
                    continue;
                }

                $tongGheCoTheBan++;

                $ve = $veTheoGhe->get($ghe->maGhe);

                if ($ve) {
                    $soGheDangBiChiem++;

                    /*
                     * Chỉ thay đổi dữ liệu trả về JSON,
                     * không save xuống bảng ghes.
                     */
                    $ghe->setAttribute(
                        'trangThai',
                        $ve->trangThai
                    );
                }
            }
        }

        $lichChieu->setAttribute(
            'gheTrong',
            max(
                $tongGheCoTheBan - $soGheDangBiChiem,
                0
            )
        );

        return response()->json([
            'message' =>
                'Lấy thông tin lịch chiếu thành công.',

            'data' => $lichChieu,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CẬP NHẬT LỊCH CHIẾU
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        string $maLichChieu
    ) {
        $lichChieu = LichChieu::findOrFail(
            $maLichChieu
        );

        if ($this->daCoKhachDatVe($maLichChieu)) {
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

        $lichChieu->update($data);

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
    |-------------------------------------------------------------------------
    */
    public function destroy(
        Request $request,
        string $maLichChieu
    ) {
        $lichChieu = LichChieu::findOrFail(
            $maLichChieu
        );

        if ($this->daCoKhachDatVe($maLichChieu)) {
            return response()->json([
                'message' =>
                    'Không thể hủy do đã có vé được bán.',
            ], 409);
        }

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
    | QUERY VÉ ĐANG CHIẾM GHẾ
    |-------------------------------------------------------------------------
    */
    private function queryVeDangChiemGhe(
        string $maLichChieu
    ) {
        return VeGhe::where(
            'maLichChieu',
            $maLichChieu
        )
            ->where(function ($ticketQuery) {
                $ticketQuery
                    ->where(
                        'trangThai',
                        'DA_SU_DUNG'
                    )
                    ->orWhere(
                        function ($activeQuery) {
                            $activeQuery
                                ->whereIn(
                                    'trangThai',
                                    [
                                        'GIU_CHO',
                                        'DA_DAT',
                                    ]
                                )
                                ->whereHas(
                                    'donHang',
                                    function ($query) {
                                        $query
                                            ->whereIn(
                                                'trangThai',
                                                [
                                                    'DA_THANH_TOAN',
                                                    'DA_SU_DUNG',
                                                ]
                                            )
                                            ->orWhere(
                                                function (
                                                    $pendingQuery
                                                ) {
                                                    $pendingQuery
                                                        ->where(
                                                            'trangThai',
                                                            'CHO_THANH_TOAN'
                                                        )
                                                        ->where(
                                                            function (
                                                                $expiryQuery
                                                            ) {
                                                                $expiryQuery
                                                                    ->where(
                                                                        'hetHanLuc',
                                                                        '>',
                                                                        now()
                                                                    )
                                                                    ->orWhere(
                                                                        function (
                                                                            $fallbackQuery
                                                                        ) {
                                                                            $fallbackQuery
                                                                                ->whereNull(
                                                                                    'hetHanLuc'
                                                                                )
                                                                                ->where(
                                                                                    'ngayDat',
                                                                                    '>',
                                                                                    now()
                                                                                        ->subMinutes(
                                                                                            10
                                                                                        )
                                                                                );
                                                                        }
                                                                    );
                                                            }
                                                        );
                                                }
                                            );
                                    }
                                );
                        }
                    );
            });
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
        $query = LichChieu::where(
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
        $batDau = Carbon::createFromFormat(
            'Y-m-d H:i',
            $ngayChieu . ' ' . $gioBatDau,
            'Asia/Ho_Chi_Minh'
        );

        $now = Carbon::now(
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
    | KIỂM TRA ĐÃ CÓ KHÁCH ĐẶT / GIỮ VÉ
    |--------------------------------------------------------------------------
    */
    private function daCoKhachDatVe(
        string $maLichChieu
    ): bool {
        return $this
            ->queryVeDangChiemGhe(
                $maLichChieu
            )
            ->exists();
    }
}
