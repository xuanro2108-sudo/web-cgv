<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phim extends Model
{
    public function statusForDates(): string
    {
        $today = now('Asia/Ho_Chi_Minh')->toDateString();
        if ($this->ngayKetThuc && $this->ngayKetThuc->toDateString() < $today) {
            return 'DA_CHIEU';
        }
        if ($this->ngayKhoiChieu) {
            return $this->ngayKhoiChieu->toDateString() > $today ? 'SAP_CHIEU' : 'DANG_CHIEU';
        }

        return $this->trangThai ?: 'SAP_CHIEU';
    }

    public static function synchronizeStatuses(): int
    {
        $today = now('Asia/Ho_Chi_Minh')->toDateString();
        $ended = static::whereDate('ngayKetThuc', '<', $today)
            ->where('trangThai', '!=', 'DA_CHIEU')->update(['trangThai' => 'DA_CHIEU']);
        $current = static::whereDate('ngayKhoiChieu', '<=', $today)
            ->where(fn ($query) => $query->whereNull('ngayKetThuc')->orWhereDate('ngayKetThuc', '>=', $today))
            ->where('trangThai', '!=', 'DANG_CHIEU')->update(['trangThai' => 'DANG_CHIEU']);
        $upcoming = static::whereDate('ngayKhoiChieu', '>', $today)
            ->where(fn ($query) => $query->whereNull('ngayKetThuc')->orWhereDate('ngayKetThuc', '>=', $today))
            ->where('trangThai', '!=', 'SAP_CHIEU')->update(['trangThai' => 'SAP_CHIEU']);

        return $ended + $current + $upcoming;
    }

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
