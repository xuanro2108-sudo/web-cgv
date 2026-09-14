<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\KhuyenMai;
use App\Models\OrderAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class KhuyenMaiController extends Controller
{
    public function management(Request $request): JsonResponse
    {
        OrderAccess::staff($request, true);

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'trangThai' => [
                'nullable',
                Rule::in(['HOAT_DONG', 'NGUNG_HOAT_DONG']),
            ],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $query = KhuyenMai::withCount('donHangs');

        if (!empty($data['trangThai'])) {
            $query->where('trangThai', $data['trangThai']);
        }

        if (($search = trim($data['q'] ?? '')) !== '') {
            $pattern = '%'
                . str_replace(
                    ['!', '%', '_'],
                    ['!!', '!%', '!_'],
                    $search
                )
                . '%';

            $query->where(function (Builder $query) use ($pattern): void {
                $query
                    ->whereRaw(
                        "maKM LIKE ? ESCAPE '!'",
                        [$pattern]
                    )
                    ->orWhereRaw(
                        "tenKM LIKE ? ESCAPE '!'",
                        [$pattern]
                    );
            });
        }

        return response()->json(
            $query
                ->orderByDesc('created_at')
                ->orderBy('maKM')
                ->paginate(10)
        );
    }

    public function index(): JsonResponse
    {
        return response()->json(
            KhuyenMai::where('trangThai', 'HOAT_DONG')
                ->whereDate('ngayBatDau', '<=', today())
                ->whereDate('ngayKetThuc', '>=', today())
                ->orderBy('maKM')
                ->paginate(20)
        );
    }

    public function show(string $maKM): JsonResponse
    {
        $promotion = KhuyenMai::where('trangThai', 'HOAT_DONG')
            ->whereDate('ngayBatDau', '<=', today())
            ->whereDate('ngayKetThuc', '>=', today())
            ->findOrFail($maKM);

        return response()->json([
            'data' => $promotion,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        OrderAccess::staff($request, true);

        $data = $this->validated($request);

        $data['maKM'] = $request->validate([
            'maKM' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                'unique:khuyen_mais,maKM',
            ],
        ])['maKM'];

        $promotion = KhuyenMai::create($data);

        return response()->json([
            'data' => $promotion,
        ], 201);
    }

    public function update(Request $request, string $maKM): JsonResponse
    {
        OrderAccess::staff($request, true);

        $promotion = DB::transaction(function () use ($request, $maKM) {
            $promotion = KhuyenMai::whereKey($maKM)
                ->lockForUpdate()
                ->firstOrFail();

            $data = $this->validated($request, $promotion);

            if ($promotion->donHangs()->exists()) {
                $allowedFields = [
                    'tenKM',
                    'hinhAnh',
                    'trangThai',
                ];

                $invalidFields = array_diff(
                    array_keys($data),
                    $allowedFields
                );

                abort_if(
                    count($invalidFields) > 0,
                    409,
                    'Mã đã được dùng; chỉ có thể sửa tên, hình ảnh và trạng thái.'
                );
            }

            $promotion->update($data);

            return $promotion->fresh();
        }, 3);

        return response()->json([
            'data' => $promotion,
        ]);
    }

    public function destroy(
        Request $request,
        string $maKM
    ): JsonResponse {
        OrderAccess::staff($request, true);

        $promotion = DB::transaction(function () use ($maKM) {
            $promotion = KhuyenMai::whereKey($maKM)
                ->lockForUpdate()
                ->firstOrFail();

            $promotion->update([
                'trangThai' => 'NGUNG_HOAT_DONG',
            ]);

            return $promotion->fresh();
        }, 3);

        return response()->json([
            'data' => $promotion,
        ]);
    }

    private function validated(
        Request $request,
        ?KhuyenMai $promotion = null
    ): array {
        $required = $promotion ? 'sometimes' : 'required';

        $data = $request->validate([
            'hinhAnh' => [
                $required,
                'nullable',
                'url',
                'max:500',
            ],

            'tenKM' => [
                $required,
                'string',
                'max:255',
            ],

            'hinhThuc' => [
                $required,
                Rule::in([
                    'GIAM_PHAN_TRAM',
                    'GIAM_GIA',
                ]),
            ],

            'giaTri' => [
                $required,
                'numeric',
                'min:0.01',
                'max:99999999.99',
                'decimal:0,2',
            ],

            'donToiThieu' => [
                $required,
                'numeric',
                'min:0',
                'max:99999999.99',
                'decimal:0,2',
            ],

            'ngayBatDau' => [
                $required,
                'date_format:Y-m-d',
            ],

            'ngayKetThuc' => [
                $required,
                'date_format:Y-m-d',
            ],

            'trangThai' => [
                'sometimes',
                Rule::in([
                    'HOAT_DONG',
                    'NGUNG_HOAT_DONG',
                ]),
            ],
        ]);

        if ($promotion) {
            $merged = [
                'hinhThuc' => $promotion->hinhThuc,
                'giaTri' => $promotion->giaTri,
                'ngayBatDau' => $promotion->ngayBatDau->format('Y-m-d'),
                'ngayKetThuc' => $promotion->ngayKetThuc->format('Y-m-d'),
            ];

            $merged = array_merge($merged, $data);
        } else {
            $merged = $data;
        }

        Validator::make($merged, [
            'ngayKetThuc' => [
                'after_or_equal:ngayBatDau',
            ],

            'giaTri' => (
                ($merged['hinhThuc'] ?? null)
                === 'GIAM_PHAN_TRAM'
            )
                ? [
                    'numeric',
                    'max:100',
                ]
                : [
                    'numeric',
                ],
        ])->validate();

        return $data;
    }
}
