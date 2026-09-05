<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChiTietComboDonHang extends Model
{
    protected $table = 'chi_tiet_combo_don_hangs';

    protected $primaryKey = 'maChiTiet';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maChiTiet',
        'maDonHang',
        'maCombo',
        'soLuong',
        'donGia',
        'thanhTien',
    ];

    protected $casts = [
        'soLuong' => 'integer',
        'donGia' => 'decimal:2',
        'thanhTien' => 'decimal:2',
    ];

    // Chi tiết combo thuộc về một đơn hàng
    public function donHang(): BelongsTo
    {
        return $this->belongsTo(
            DonHang::class,
            'maDonHang',
            'maDonHang'
        );
    }

    // Chi tiết combo thuộc về một combo
    public function combo(): BelongsTo
    {
        return $this->belongsTo(
            Combo::class,
            'maCombo',
            'maCombo'
        );
    }
}