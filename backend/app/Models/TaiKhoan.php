<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaiKhoan extends Model
{
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
    return $this->belongsTo(KhachHang::class, 'maKH', 'maKH');
}

public function nhanVien()
{
    return $this->belongsTo(NhanVien::class, 'maNV', 'maNV');
}
}
