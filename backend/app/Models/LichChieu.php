<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LichChieu extends Model
{
    protected $table = 'lich_chieus';

    protected $primaryKey = 'maLichChieu';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maLichChieu',
        'maPhim',
        'maPhong',
        'ngayChieu',
        'gioBatDau',
        'gioKetThuc',
        'giaVeCoBan',
        'trangThai',
    ];

    protected $casts = [
        'ngayChieu' => 'date',
        'gioBatDau' => 'datetime:H:i',
        'gioKetThuc' => 'datetime:H:i',
        'giaVeCoBan' => 'decimal:2',
    ];

    public function phim(): BelongsTo
    {
        return $this->belongsTo(Phim::class, 'maPhim', 'maPhim');
    }

    public function phongChieu(): BelongsTo
    {
        return $this->belongsTo(PhongChieu::class, 'maPhong', 'maPhong');
    }
    public function tinhGiaVe(string $loaiGhe): float
{
    return match ($loaiGhe) {
        'THUONG' => (float) $this->giaVeCoBan,

        'VIP' => (float) $this->giaVeCoBan * 1.2,

        'DOI' => (float) $this->giaVeCoBan * 1.2 * 2,

        default => throw new \InvalidArgumentException(
            "Loại ghế không hợp lệ: {$loaiGhe}"
        ),
    };
}
}