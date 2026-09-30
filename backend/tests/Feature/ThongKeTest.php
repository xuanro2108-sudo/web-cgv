<?php

namespace Tests\Feature;

use App\Models\KhuyenMai;
use App\Models\ThanhToan;
use App\Models\VeGhe;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ThongKeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_movie_statistics_use_the_payment_date_and_include_used_tickets(): void
    {
        $this->freezeTime();
        $manager = $this->customer();
        $manager->update(['vaiTro' => 'QUAN_LY']);
        $order = $this->order($manager);
        $this->seat();

        $order->update(['trangThai' => 'DA_SU_DUNG']);
        KhuyenMai::create([
            'maKM' => 'SALE10',
            'tenKM' => 'Sale 10%',
            'hinhThuc' => 'GIAM_PHAN_TRAM',
            'giaTri' => 10,
            'donToiThieu' => 200000,
            'ngayBatDau' => today(),
            'ngayKetThuc' => today(),
            'trangThai' => 'HOAT_DONG',
        ]);
        $order->update(['maKM' => 'SALE10']);
        VeGhe::create([
            'maVe' => 'VE'.Str::ulid(),
            'maDonHang' => $order->maDonHang,
            'maLichChieu' => 'L1',
            'maGhe' => 'G1',
            'giaVe' => 100000,
            'trangThai' => 'DA_SU_DUNG',
            'ngayTao' => now(),
        ]);
        ThanhToan::create([
            'maTT' => 'TT'.Str::ulid(),
            'maDonHang' => $order->maDonHang,
            'maGiaoDich' => 'GD'.Str::ulid(),
            'soTien' => 100000,
            'phuongThuc' => 'GIA_LAP',
            'ngayThanhToan' => now(),
            'trangThai' => 'THANH_CONG',
        ]);

        Sanctum::actingAs($manager);

        $this->getJson('/api/quan-ly/thong-ke/doanh-thu-ve?tuNgay='.today()->toDateString().'&denNgay='.today()->toDateString())
            ->assertOk()
            ->assertJsonPath('tongSoVe', 1)
            ->assertJsonPath('tongDoanhThu', 100000.0);

        $this->getJson('/api/quan-ly/thong-ke/theo-phim?tuNgay='.today()->toDateString().'&denNgay='.today()->toDateString())
            ->assertOk()
            ->assertJsonPath('tongSoVe', 1)
            ->assertJsonPath('tongDoanhThu', 100000.0);

        $this->getJson('/api/quan-ly/thong-ke/theo-phim?tuNgay='.today()->addDay()->toDateString().'&denNgay='.today()->addDay()->toDateString())
            ->assertOk()
            ->assertJsonPath('coDuLieu', false);

        $this->getJson('/api/quan-ly/thong-ke/khuyen-mai?tuNgay='.today()->toDateString().'&denNgay='.today()->toDateString())
            ->assertOk()
            ->assertJsonPath('tongLuotSuDung', 0)
            ->assertJsonPath('tongTienGiam', 0.0)
            ->assertJsonPath('coDuLieu', false);
    }
}
