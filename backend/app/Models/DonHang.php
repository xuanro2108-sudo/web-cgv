<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DonHang extends Model
{
    protected $table = 'don_hangs';

    protected $primaryKey = 'maDonHang';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maDonHang',
        'maKH',
        'maNV',
        'maKM',
        'kieuDat',
        'ngayDat',
        'tongTien',
        'maQR',
        'trangThai',
        'hetHanLuc',
    ];

    protected $casts = [
        'ngayDat' => 'datetime',
        'hetHanLuc' => 'datetime',
        'tongTien' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (DonHang $order): void {
            $order->hetHanLuc ??= now()->addMinutes(10);
        });
    }

    public function daHetHan(): bool
    {
        return ($this->hetHanLuc ?? $this->ngayDat->copy()->addMinutes(10))->lte(now());
    }

    /** The caller must hold the order row lock inside a transaction. */
    public function huy(string $status = 'DA_HUY'): void
    {
        $this->veGhes()->where('trangThai', 'GIU_CHO')->update(['trangThai' => 'DA_HUY']);
        $this->thanhToan()->where('trangThai', 'CHO_THANH_TOAN')->update(['trangThai' => 'DA_HUY']);
        $this->update(['trangThai' => $status]);
    }

    // Đơn hàng thuộc về một khách hàng
    public function khachHang(): BelongsTo
    {
        return $this->belongsTo(
            KhachHang::class,
            'maKH',
            'maKH'
        );
    }

    // Đơn hàng có thể do một nhân viên xử lý
    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(
            NhanVien::class,
            'maNV',
            'maNV'
        );
    }

    // Đơn hàng có thể sử dụng một khuyến mãi
    public function khuyenMai(): BelongsTo
    {
        return $this->belongsTo(
            KhuyenMai::class,
            'maKM',
            'maKM'
        );
    }

    // Một đơn hàng có nhiều vé ghế
    public function veGhes(): HasMany
    {
        return $this->hasMany(
            VeGhe::class,
            'maDonHang',
            'maDonHang'
        );
    }

    // Một đơn hàng có nhiều dòng combo
    public function chiTietComboDonHangs(): HasMany
    {
        return $this->hasMany(
            ChiTietComboDonHang::class,
            'maDonHang',
            'maDonHang'
        );
    }

    // Một đơn hàng có tối đa một thanh toán
    public function thanhToan(): HasOne
    {
        return $this->hasOne(
            ThanhToan::class,
            'maDonHang',
            'maDonHang'
        );
    }

    public function tamTinh(): float
    {
        $tongTienVe = $this->veGhes()->whereIn('trangThai', ['GIU_CHO', 'DA_DAT'])->sum('giaVe');

        $tongTienCombo = $this->chiTietComboDonHangs()->sum('thanhTien');

        return round((float) $tongTienVe + (float) $tongTienCombo, 2);
    }

    public function tinhTongTien(): float
    {
        $subtotal = $this->tamTinh();
        $promotion = $this->khuyenMai()->first();

        return round($subtotal - ($promotion?->tienGiam($subtotal) ?? 0), 2);
    }
}
