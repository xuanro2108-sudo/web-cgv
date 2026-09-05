<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeGhe extends Model
{
    protected $table = 've_ghes';

    protected $primaryKey = 'maVe';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maVe',
        'maDonHang',
        'maLichChieu',
        'maGhe',
        'giaVe',
        'trangThai',
        'ngayTao',
    ];

    protected $casts = [
        'giaVe' => 'decimal:2',
        'ngayTao' => 'datetime',
    ];

    public function donHang(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'maDonHang', 'maDonHang');
    }

    public function lichChieu(): BelongsTo
    {
        return $this->belongsTo(LichChieu::class, 'maLichChieu', 'maLichChieu');
    }

    public function ghe(): BelongsTo
    {
        return $this->belongsTo(Ghe::class, 'maGhe', 'maGhe');
    }
}