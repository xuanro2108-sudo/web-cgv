<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoDoGhe extends Model
{
    protected $table = 'so_do_ghes';

    protected $primaryKey = 'maSoDo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maSoDo',
        'maPhong',
    ];

    public function phongChieu(): BelongsTo
    {
        return $this->belongsTo(PhongChieu::class, 'maPhong', 'maPhong');
    }

    public function ghes(): HasMany
    {
        return $this->hasMany(Ghe::class, 'maSoDo', 'maSoDo');
    }
}