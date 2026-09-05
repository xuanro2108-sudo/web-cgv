<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phim extends Model
{
    protected $table = 'phims';

    protected $primaryKey = 'maPhim';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maPhim',
        'tenPhim',
        'theLoai',
        'thoiLuong',
        'daoDien',
        'dienVien',
        'ngayKhoiChieu',
        'ngayKetThuc',
        'moTa',
        'hinhAnh',
        'trailer',
        'trangThai',
    ];

    protected $casts = [
        'ngayKhoiChieu' => 'date',
        'ngayKetThuc' => 'date',
        'thoiLuong' => 'integer',
    ];

    public function lichChieus(): HasMany
    {
        return $this->hasMany(LichChieu::class, 'maPhim', 'maPhim');
    }
}