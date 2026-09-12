<?php

namespace Tests\Feature;

use App\Models\DonHang;
use App\Models\NhanVien;
use App\Models\VeGhe;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GheDaSuDungTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_showtime_marks_used_seat_as_da_su_dung_and_decreases_ghe_trong(): void
    {
        $this->freezeTime();
        $user = $this->customer();
        $order = $this->order($user);
        $this->seat(); // G1, L1, R1

        $order->update(['trangThai' => 'DA_SU_DUNG']);
        VeGhe::create([
            'maVe' => 'VE_USED_1',
            'maDonHang' => $order->maDonHang,
            'maLichChieu' => 'L1',
            'maGhe' => 'G1',
            'giaVe' => 100000,
            'trangThai' => 'DA_SU_DUNG',
            'ngayTao' => now(),
        ]);

        // GET /api/lich-chieus/L1
        $showtimeResponse = $this->getJson('/api/lich-chieus/L1')->assertOk();
        $seats = $showtimeResponse->json('data.phong_chieu.so_do_ghe.ghes');
        $this->assertNotEmpty($seats);
        $g1 = collect($seats)->firstWhere('maGhe', 'G1');
        $this->assertNotNull($g1);
        $this->assertSame('DA_SU_DUNG', $g1['trangThai']);

        // GET /api/lich-chieus
        $listResponse = $this->getJson('/api/lich-chieus')->assertOk();
        $lichChieu = collect($listResponse->json('data'))->firstWhere('maLichChieu', 'L1');
        $this->assertNotNull($lichChieu);
        $this->assertSame(0, $lichChieu['gheTrong']);
    }

    public function test_another_user_cannot_reserve_or_hold_used_seat(): void
    {
        $this->freezeTime();
        $user1 = $this->customer();
        $order1 = $this->order($user1);
        $this->seat(); // G1, L1

        $order1->update(['trangThai' => 'DA_SU_DUNG']);
        VeGhe::create([
            'maVe' => 'VE_USED_2',
            'maDonHang' => $order1->maDonHang,
            'maLichChieu' => 'L1',
            'maGhe' => 'G1',
            'giaVe' => 100000,
            'trangThai' => 'DA_SU_DUNG',
            'ngayTao' => now(),
        ]);

        // Customer 2 tries to reserve seat G1
        $user2 = $this->customer();
        $order2 = $this->order($user2);
        Sanctum::actingAs($user2);

        $response = $this->postJson('/api/ve-ghes', [
            'maDonHang' => $order2->maDonHang,
            'maLichChieu' => 'L1',
            'maGhe' => 'G1',
        ]);

        $response->assertConflict();
        $response->assertJsonFragment(['message' => 'Ghế đã được giữ hoặc đặt.']);
    }

    public function test_staff_cannot_sell_used_seat_at_counter(): void
    {
        $this->freezeTime();
        $user = $this->customer();
        $order = $this->order($user);
        $this->seat(); // G1, L1

        $order->update(['trangThai' => 'DA_SU_DUNG']);
        VeGhe::create([
            'maVe' => 'VE_USED_3',
            'maDonHang' => $order->maDonHang,
            'maLichChieu' => 'L1',
            'maGhe' => 'G1',
            'giaVe' => 100000,
            'trangThai' => 'DA_SU_DUNG',
            'ngayTao' => now(),
        ]);

        // Staff login
        $staffUser = $this->customer();
        NhanVien::create([
            'maNV' => 'NV_COUNTER',
            'hoTen' => 'Staff Counter',
            'sdt' => '0912345678',
            'email' => 'staff_counter@example.com',
            'chucVu' => 'NHAN_VIEN',
            'ngayVaoLam' => today(),
            'trangThai' => 'DANG_LAM',
        ]);
        $staffUser->update(['vaiTro' => 'NHAN_VIEN', 'maNV' => 'NV_COUNTER']);
        Sanctum::actingAs($staffUser);

        $response = $this->postJson('/api/quan-ly/ban-ve-tai-quay', [
            'hoTen' => 'Khách mua tại quầy',
            'soDienThoai' => '0987654321',
            'maLichChieu' => 'L1',
            'maGhes' => ['G1'],
        ]);

        $response->assertConflict();
        $response->assertJsonFragment(['message' => 'Một hoặc nhiều ghế vừa được giữ/đặt. Vui lòng chọn lại.']);
    }

    public function test_order_tam_tinh_includes_da_su_dung_tickets(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        $this->seat();

        $order->update(['trangThai' => 'DA_SU_DUNG']);
        VeGhe::create([
            'maVe' => 'VE_USED_4',
            'maDonHang' => $order->maDonHang,
            'maLichChieu' => 'L1',
            'maGhe' => 'G1',
            'giaVe' => 100000,
            'trangThai' => 'DA_SU_DUNG',
            'ngayTao' => now(),
        ]);

        $this->assertSame(100000.0, $order->fresh()->tamTinh());
    }

    public function test_database_constraint_rejects_duplicate_active_ticket_for_used_seat(): void
    {
        $user = $this->customer();
        $order1 = $this->order($user);
        $order2 = $this->order($user);
        $this->seat();

        VeGhe::create([
            'maVe' => 'VE_USED_5',
            'maDonHang' => $order1->maDonHang,
            'maLichChieu' => 'L1',
            'maGhe' => 'G1',
            'giaVe' => 100000,
            'trangThai' => 'DA_SU_DUNG',
            'ngayTao' => now(),
        ]);

        $this->expectException(QueryException::class);

        VeGhe::create([
            'maVe' => 'VE_HOLD_5',
            'maDonHang' => $order2->maDonHang,
            'maLichChieu' => 'L1',
            'maGhe' => 'G1',
            'giaVe' => 100000,
            'trangThai' => 'GIU_CHO',
            'ngayTao' => now(),
        ]);
    }
}
