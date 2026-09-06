<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KhuyenMai extends Model
{
    public function apDungDuoc(float $subtotal): bool
    {
        return $this->trangThai === 'HOAT_DONG'
            && $this->ngayBatDau->lte(today()) && $this->ngayKetThuc->gte(today())
            && $subtotal >= (float) $this->donToiThieu
            && in_array($this->hinhThuc, ['GIAM_PHAN_TRAM', 'GIAM_GIA'], true)
            && (float) $this->giaTri > 0
            && ($this->hinhThuc !== 'GIAM_PHAN_TRAM' || (float) $this->giaTri <= 100);
    }

    public function tienGiam(float $subtotal): float
    {
        if (! $this->apDungDuoc($subtotal)) {
            return 0;
        }

        return round(min($subtotal, $this->hinhThuc === 'GIAM_PHAN_TRAM' ? $subtotal * (float) $this->giaTri / 100 : (float) $this->giaTri), 2);
    }

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
