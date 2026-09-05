<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KhachHang extends Model
{
    protected $table = 'khach_hangs';

    protected $primaryKey = 'maKH';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maKH',
        'hoTen',
        'soDienThoai',
        'email',
        'ngaySinh',
        'gioiTinh',
        'ngayDangKy',
        'trangThai',
    ];

    protected $casts = [
        'ngaySinh' => 'date',
        'ngayDangKy' => 'date',
    ];
    public function taiKhoan()
{
    return $this->hasOne(TaiKhoan::class, 'maKH', 'maKH');
}
}