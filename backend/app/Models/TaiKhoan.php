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
        'trangThai',
    ];

    protected $hidden = [
        'matKhau',
    ];
}