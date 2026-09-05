<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ghe extends Model
{
    protected $table = 'ghes';

    protected $primaryKey = 'maGhe';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maGhe',
        'maSoDo',
        'hang',
        'cot',
        'loaiGhe',
        'trangThai',
    ];

    protected $casts = [
        'cot' => 'integer',
    ];

    public function soDoGhe(): BelongsTo
    {
        return $this->belongsTo(SoDoGhe::class, 'maSoDo', 'maSoDo');
    }

    public function veGhes(): HasMany
    {
        return $this->hasMany(VeGhe::class, 'maGhe', 'maGhe');
    }
}