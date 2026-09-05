<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class TaiKhoan extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'tai_khoans';

    protected $primaryKey = 'maTK';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maTK',
        'tenDangNhap',
        'matKhau',
        'vaiTro',
        'maKH',
        'maNV',
        'trangThai',
    ];

    protected $hidden = [
        'matKhau',
    ];

    public function khachHang()
    {
        return $this->belongsTo(
            KhachHang::class,
            'maKH',
            'maKH'
        );
    }

    public function nhanVien()
    {
        return $this->belongsTo(
            NhanVien::class,
            'maNV',
            'maNV'
        );
    }
}