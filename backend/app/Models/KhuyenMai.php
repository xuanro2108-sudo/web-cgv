<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KhuyenMai extends Model
{
    protected $table = 'khuyen_mais';

    protected $primaryKey = 'maKM';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maKM',
        'tenKM',
        'hinhThuc',
        'giaTri',
        'donToiThieu',
        'ngayBatDau',
        'ngayKetThuc',
        'trangThai',
    ];

    protected $casts = [
        'giaTri' => 'decimal:2',
        'donToiThieu' => 'decimal:2',
        'ngayBatDau' => 'date',
        'ngayKetThuc' => 'date',
    ];

    // Một khuyến mãi có thể được áp dụng cho nhiều đơn hàng
    public function donHangs(): HasMany
    {
        return $this->hasMany(
            DonHang::class,
            'maKM',
            'maKM'
        );
    }
}