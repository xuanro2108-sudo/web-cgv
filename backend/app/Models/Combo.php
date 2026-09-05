<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Combo extends Model
{
    protected $table = 'combos';

    protected $primaryKey = 'maCombo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maCombo',
        'tenCombo',
        'donGia',
        'moTa',
        'hinhAnh',
        'trangThai',
    ];

    protected $casts = [
        'donGia' => 'decimal:2',
    ];

    // Một combo gồm nhiều sản phẩm
    public function chiTietCombos(): HasMany
    {
        return $this->hasMany(
            ChiTietCombo::class,
            'maCombo',
            'maCombo'
        );
    }

    // Một combo có thể xuất hiện trong nhiều đơn hàng
    public function chiTietComboDonHangs(): HasMany
    {
        return $this->hasMany(
            ChiTietComboDonHang::class,
            'maCombo',
            'maCombo'
        );
    }
}