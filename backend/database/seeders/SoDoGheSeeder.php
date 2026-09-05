<?php

namespace Database\Seeders;

use App\Models\SoDoGhe;
use Illuminate\Database\Seeder;

class SoDoGheSeeder extends Seeder
{
    public function run(): void
    {
        SoDoGhe::create([
            'maSoDo' => 'SD001',
            'maPhong' => 'P001',
        ]);

        SoDoGhe::create([
            'maSoDo' => 'SD002',
            'maPhong' => 'P002',
        ]);

        SoDoGhe::create([
            'maSoDo' => 'SD003',
            'maPhong' => 'P003',
        ]);

        SoDoGhe::create([
            'maSoDo' => 'SD004',
            'maPhong' => 'P004',
        ]);
    }
}
