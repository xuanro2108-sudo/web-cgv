<?php

namespace Tests\Feature;

use App\Models\LichChieu;
use App\Models\Phim;
use App\Models\ThanhToan;
use App\Models\VeGhe;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class FeaturedMovieTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_public_feature_returns_highest_paid_ticket_revenue_across_all_movies(): void
    {
        $this->freezeTime();
        $this->seat();
        Phim::findOrFail('P1')->update(['trangThai' => 'DA_CHIEU']);
        $this->movieWithTicket('WIN', 200000, 'DA_SU_DUNG');
        $this->movieWithTicket('THIRD', 50000);
        $this->movieWithTicket('LOW', 150000);
        $this->movieWithTicket('UNPAID', 900000, 'DA_DAT', 'CHO_THANH_TOAN');
        $this->movieWithTicket('CANCELLED', 900000, 'DA_HUY');
        for ($i = 0; $i < 11; $i++) {
            Phim::create(['maPhim' => 'NEW'.$i, 'tenPhim' => 'New movie', 'trangThai' => 'DANG_CHIEU']);
        }

        $this->getJson('/api/phims/noi-bat')->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.maPhim', 'WIN')
            ->assertJsonPath('data.1.maPhim', 'LOW')
            ->assertJsonPath('data.2.maPhim', 'THIRD')
            ->assertJsonMissingPath('data.0.doanhThu');
    }

    #[TestWith(['SAP_CHIEU', null, null])]
    #[TestWith(['DA_CHIEU', null, null])]
    #[TestWith(['DANG_CHIEU', null, -1])]
    #[TestWith(['DANG_CHIEU', 1, null])]
    public function test_ineligible_movies_are_excluded_even_with_higher_revenue(string $status, ?int $start, ?int $end): void
    {
        $this->freezeTime();
        $this->seat();
        Phim::findOrFail('P1')->update(['trangThai' => 'DA_CHIEU']);
        $this->movieWithTicket('ELIGIBLE', 100000)->update(['ngayKhoiChieu' => today(), 'ngayKetThuc' => today()]);
        $this->movieWithTicket('EXCLUDED', 900000)->update([
            'trangThai' => $status,
            'ngayKhoiChieu' => $start === null ? null : today()->addDays($start),
            'ngayKetThuc' => $end === null ? null : today()->addDays($end),
        ]);

        $this->getJson('/api/phims/noi-bat')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.maPhim', 'ELIGIBLE');
    }

    public function test_no_current_movies_returns_empty_list(): void
    {
        $this->getJson('/api/phims/noi-bat')->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_zero_revenue_ties_choose_latest_release_then_movie_id(): void
    {
        $this->freezeTime();
        foreach (['B', 'A', 'C'] as $id) {
            Phim::create(['maPhim' => $id, 'tenPhim' => $id, 'trangThai' => 'DANG_CHIEU', 'ngayKhoiChieu' => $id === 'C' ? today()->subDay() : today()]);
        }
        $this->getJson('/api/phims/noi-bat')->assertOk()->assertJsonPath('data.0.maPhim', 'A')->assertJsonPath('data.1.maPhim', 'B')->assertJsonPath('data.2.maPhim', 'C');
    }

    private function movieWithTicket(string $id, int $price, string $ticketStatus = 'DA_DAT', string $paymentStatus = 'THANH_CONG'): Phim
    {
        $movie = Phim::create(['maPhim' => $id, 'tenPhim' => $id, 'trangThai' => 'DANG_CHIEU']);
        $showtime = LichChieu::findOrFail('L1')->replicate();
        $showtime->maLichChieu = 'L'.$id;
        $showtime->maPhim = $id;
        $showtime->save();
        $order = $this->order($this->customer());
        VeGhe::create(['maVe' => 'V'.$id, 'maDonHang' => $order->maDonHang, 'maLichChieu' => $showtime->maLichChieu, 'maGhe' => 'G1', 'giaVe' => $price, 'trangThai' => $ticketStatus, 'ngayTao' => now()]);
        ThanhToan::create(['maTT' => 'T'.$id, 'maDonHang' => $order->maDonHang, 'maGiaoDich' => 'GD'.Str::ulid(), 'soTien' => $price, 'phuongThuc' => 'GIA_LAP', 'ngayThanhToan' => now(), 'trangThai' => $paymentStatus]);

        return $movie;
    }
}
