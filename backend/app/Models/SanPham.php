<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SanPham extends Model
{
    protected $table = 'san_phams';

    protected $primaryKey = 'maSP';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maSP',
        'tenSP',
        'loaiSP',
        'donGia',
        'hinhAnh',
        'moTa',
        'trangThai',
    ];

    protected $casts = [
        'donGia' => 'decimal:2',
    ];

    // Một sản phẩm có thể thuộc nhiều combo
    public function chiTietCombos(): HasMany
    {
        return $this->hasMany(
            ChiTietCombo::class,
            'maSP',
            'maSP'
        );
    }
}