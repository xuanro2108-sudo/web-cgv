<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChiTietCombo extends Model
{
    protected $table = 'chi_tiet_combos';

    protected $primaryKey = 'maCTCombo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maCTCombo',
        'maCombo',
        'maSP',
        'soLuong',
    ];

    protected $casts = [
        'soLuong' => 'integer',
    ];

    // Chi tiết combo thuộc về một combo
    public function combo(): BelongsTo
    {
        return $this->belongsTo(
            Combo::class,
            'maCombo',
            'maCombo'
        );
    }

    // Chi tiết combo thuộc về một sản phẩm
    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(
            SanPham::class,
            'maSP',
            'maSP'
        );
    }
}