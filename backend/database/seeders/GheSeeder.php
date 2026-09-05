<?php

namespace Database\Seeders;

use App\Models\Ghe;
use Illuminate\Database\Seeder;

class GheSeeder extends Seeder
{
    public function run(): void
    {
        // P001 - 80 gháº¿
        $this->taoGhe('SD001', 80, 1);

        // P002 - 100 gháº¿
        $this->taoGhe('SD002', 100, 81);

        // P003 - 120 gháº¿
        $this->taoGhe('SD003', 120, 181);

        // P004 - 60 gháº¿
        $this->taoGhe('SD004', 60, 301);
    }

    private function taoGhe(
        string $maSoDo,
        int $soLuong,
        int $batDau
    ): void {
        $soCot = 10;
        $soHang = (int) ceil($soLuong / $soCot);
        $maGhe = $batDau;

        for ($i = 0; $i < $soHang; $i++) {
            $hang = chr(65 + $i);

            for ($cot = 1; $cot <= $soCot; $cot++) {

                // Äá»§ sá»‘ lÆ°á»£ng gháº¿ thĂ¬ dá»«ng
                if ($maGhe >= $batDau + $soLuong) {
                    break;
                }

                // 2 hĂ ng Ä‘áº§u: gháº¿ thÆ°á»ng
                if ($i < 2) {
                    $loaiGhe = 'THUONG';
                }

                // HĂ ng cuá»‘i: gháº¿ Ä‘Ă´i
                elseif ($i === $soHang - 1) {
                    $loaiGhe = 'DOI';
                }

                // CĂ¡c hĂ ng cĂ²n láº¡i: gháº¿ VIP
                else {
                    $loaiGhe = 'VIP';
                }

                Ghe::create([
                    'maGhe' => 'G' . str_pad(
                        $maGhe,
                        3,
                        '0',
                        STR_PAD_LEFT
                    ),
                    'maSoDo' => $maSoDo,
                    'hang' => $hang,
                    'cot' => $cot,
                    'loaiGhe' => $loaiGhe,
                    'trangThai' => 'HOAT_DONG',
                ]);

                $maGhe++;
            }
        }
    }
}
