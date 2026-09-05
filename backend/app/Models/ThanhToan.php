<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThanhToan extends Model
{
    protected $table = 'thanh_toans';

    protected $primaryKey = 'maTT';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maTT',
        'maDonHang',
        'maGiaoDich',
        'soTien',
        'phuongThuc',
        'ngayThanhToan',
        'trangThai',
    ];

    protected $casts = [
        'soTien' => 'decimal:2',
        'ngayThanhToan' => 'datetime',
    ];

    // Thanh toán thuộc về một đơn hàng
    public function donHang(): BelongsTo
    {
        return $this->belongsTo(
            DonHang::class,
            'maDonHang',
            'maDonHang'
        );
    }
}