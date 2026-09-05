<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PhongChieu extends Model
{
    protected $table = 'phong_chieus';

    protected $primaryKey = 'maPhong';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maPhong',
        'tenPhong',
        'sucChua',
        'trangThai',
    ];

    protected $casts = [
        'sucChua' => 'integer',
    ];

    public function lichChieus(): HasMany
    {
        return $this->hasMany(LichChieu::class, 'maPhong', 'maPhong');
    }

    public function soDoGhe(): HasOne
    {
        return $this->hasOne(SoDoGhe::class, 'maPhong', 'maPhong');
    }
}