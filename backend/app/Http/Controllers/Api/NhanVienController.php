<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NhanVien;
use App\Models\TaiKhoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class NhanVienController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->query('search', ''));

        $query = NhanVien::with('taiKhoan');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('maNV', 'like', "%{$search}%")
                    ->orWhere('hoTen', 'like', "%{$search}%")
                    ->orWhere('sdt', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->orderBy('maNV')->paginate(10)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hoTen' => 'required|string|max:255',

            'sdt' => [
                'required',
                'regex:/^0[0-9]{9}$/',
                'unique:nhan_viens,sdt',
            ],

            'email' => [
                'required',
                'email',
                'regex:/^[A-Za-z0-9._%+\-]+@gmail\.com$/i',
                'unique:nhan_viens,email',
                Rule::unique('tai_khoans', 'tenDangNhap'),
            ],

            'chucVu' => [
                'required',
                Rule::in(['NHAN_VIEN', 'QUAN_LY']),
            ],

            'ngayVaoLam' => 'required|date|before_or_equal:today',
            'matKhau' => 'required|string|min:6',
        ]);

        $data['email'] = strtolower(trim($data['email']));
        $data['hoTen'] = trim($data['hoTen']);

        $result = DB::transaction(function () use ($data) {
            $lastNV = NhanVien::orderBy('maNV', 'desc')->first();
            $nextNV = $lastNV ? (int) substr($lastNV->maNV, 2) + 1 : 1;
            $maNV = 'NV' . str_pad($nextNV, 3, '0', STR_PAD_LEFT);

            $lastTK = TaiKhoan::orderBy('maTK', 'desc')->first();
            $nextTK = $lastTK ? (int) substr($lastTK->maTK, 2) + 1 : 1;
            $maTK = 'TK' . str_pad($nextTK, 3, '0', STR_PAD_LEFT);

            $nhanVien = NhanVien::create([
                'maNV' => $maNV,
                'hoTen' => $data['hoTen'],
                'sdt' => $data['sdt'],
                'email' => $data['email'],
                'chucVu' => $data['chucVu'],
                'ngayVaoLam' => $data['ngayVaoLam'],
                'trangThai' => 'DANG_LAM',
            ]);

            $taiKhoan = TaiKhoan::create([
                'maTK' => $maTK,
                'tenDangNhap' => $data['email'],
                'matKhau' => Hash::make($data['matKhau']),
                'vaiTro' => $data['chucVu'],
                'maKH' => null,
                'maNV' => $maNV,
                'trangThai' => 'HOAT_DONG',
            ]);

            return compact('nhanVien', 'taiKhoan');
        });

        return response()->json([
            'message' => 'Thêm nhân viên thành công',
            'nhanVien' => $result['nhanVien'],
            'taiKhoan' => $result['taiKhoan'],
        ], 201);
    }

    public function show(string $maNV)
    {
        $nhanVien = NhanVien::with('taiKhoan')->find($maNV);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        return response()->json(['data' => $nhanVien]);
    }

    public function update(Request $request, string $maNV)
    {
        $nhanVien = NhanVien::find($maNV);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        $taiKhoan = $nhanVien->taiKhoan;

        $emailRules = [
            'sometimes',
            'required',
            'email',
            'regex:/^[A-Za-z0-9._%+\-]+@gmail\.com$/i',
            Rule::unique('nhan_viens', 'email')->ignore($maNV, 'maNV'),
        ];

        $emailRules[] = $taiKhoan
            ? Rule::unique('tai_khoans', 'tenDangNhap')->ignore($taiKhoan->maTK, 'maTK')
            : Rule::unique('tai_khoans', 'tenDangNhap');

        $data = $request->validate([
            'hoTen' => 'sometimes|required|string|max:255',

            'sdt' => [
                'sometimes',
                'required',
                'regex:/^0[0-9]{9}$/',
                Rule::unique('nhan_viens', 'sdt')->ignore($maNV, 'maNV'),
            ],

            'email' => $emailRules,

            'chucVu' => [
                'sometimes',
                'required',
                Rule::in(['NHAN_VIEN', 'QUAN_LY']),
            ],

            'ngayVaoLam' => 'sometimes|required|date|before_or_equal:today',

            'trangThai' => [
                'sometimes',
                'required',
                Rule::in(['DANG_LAM', 'NGHI_VIEC']),
            ],
        ]);

        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        if (isset($data['hoTen'])) {
            $data['hoTen'] = trim($data['hoTen']);
        }

        DB::transaction(function () use ($nhanVien, $taiKhoan, $data) {
            $nhanVien->update($data);

            if (!$taiKhoan) return;

            if (isset($data['email'])) {
                $taiKhoan->tenDangNhap = $data['email'];
            }

            if (isset($data['chucVu'])) {
                $taiKhoan->vaiTro = $data['chucVu'];
            }

            if (isset($data['trangThai'])) {
                $taiKhoan->trangThai =
                    $data['trangThai'] === 'DANG_LAM'
                        ? 'HOAT_DONG'
                        : 'KHOA';

                if ($data['trangThai'] === 'NGHI_VIEC') {
                    $taiKhoan->tokens()->delete();
                }
            }

            $taiKhoan->save();
        });

        return response()->json([
            'message' => 'Cập nhật nhân viên thành công',
            'data' => $nhanVien->fresh('taiKhoan'),
        ]);
    }

    public function destroy(string $maNV)
    {
        $nhanVien = NhanVien::find($maNV);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        if ($nhanVien->trangThai === 'NGHI_VIEC') {
            return response()->json(['message' => 'Nhân viên đã nghỉ việc'], 400);
        }

        DB::transaction(function () use ($nhanVien) {
            $nhanVien->update(['trangThai' => 'NGHI_VIEC']);

            if ($nhanVien->taiKhoan) {
                $nhanVien->taiKhoan->update(['trangThai' => 'KHOA']);
                $nhanVien->taiKhoan->tokens()->delete();
            }
        });

        return response()->json([
            'message' => 'Xóa nhân viên thành công',
        ]);
    }
}