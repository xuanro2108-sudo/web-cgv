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
    ];

    protected $casts = [
        'ngayDat' => 'datetime',
        'tongTien' => 'decimal:2',
    ];

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
}