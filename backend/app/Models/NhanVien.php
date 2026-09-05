<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NhanVien extends Model
{
    protected $table = 'nhan_viens';

    protected $primaryKey = 'maNV';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maNV',
        'hoTen',
        'sdt',
        'email',
        'chucVu',
        'ngayVaoLam',
        'trangThai',
    ];

    protected $casts = [
        'ngayVaoLam' => 'date',
    ];
}