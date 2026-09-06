<?php

namespace Tests\Feature;

use App\Models\NhanVien;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ThanhToanTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_mock_success_is_idempotent_and_cannot_be_forged_for_another_order(): void
    {
        config(['payments.mock_enabled' => true]);
        $user = $this->customer();
        $order = $this->order($user);
        $this->seat();
        Sanctum::actingAs($user);
        $this->postJson('/api/ve-ghes', ['maDonHang' => $order->maDonHang, 'maLichChieu' => 'L1', 'maGhe' => 'G1'])->assertCreated();
        $url = "/api/don-hangs/{$order->maDonHang}/thanh-toan";
        $payment = $this->postJson($url, ['phuongThuc' => 'GIA_LAP', 'soTien' => 1])->assertCreated()->assertJsonPath('data.soTien', '100000.00')->json('data');
        $this->postJson($url, ['phuongThuc' => 'GIA_LAP'])->assertCreated()->assertJsonPath('data.maTT', $payment['maTT']);
        $body = ['maGiaoDich' => $payment['maGiaoDich'], 'ketQua' => 'THANH_CONG'];
        $finish = '/api/thanh-toans/'.$payment['maTT'].'/gia-lap';
        Sanctum::actingAs($this->customer());
        $this->postJson($finish, $body)->assertNotFound();
        Sanctum::actingAs($user);
        $this->postJson($finish, $body)->assertOk();
        $qr = $order->fresh()->maQR;
        $this->postJson($finish, $body)->assertOk();
        $this->assertSame($qr, $order->fresh()->maQR);
        $this->postJson($finish, [...$body, 'ketQua' => 'THAT_BAI'])->assertConflict();
        $this->assertDatabaseCount('thanh_toans', 1);
        $this->assertDatabaseHas('ve_ghes', ['maDonHang' => $order->maDonHang, 'trangThai' => 'DA_DAT']);
        $this->assertSame('DA_THANH_TOAN', $order->fresh()->trangThai);
    }

    public function test_cash_confirmation_requires_staff_and_exact_amount(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        $combo = $this->combo();
        Sanctum::actingAs($user);
        $this->postJson("/api/don-hangs/{$order->maDonHang}/combos", ['maCombo' => $combo->maCombo, 'soLuong' => 1])->assertCreated();
        $payment = $this->postJson("/api/don-hangs/{$order->maDonHang}/thanh-toan", ['phuongThuc' => 'TIEN_MAT'])->assertCreated()->json('data');
        $this->patchJson("/api/don-hangs/{$order->maDonHang}/combos/{$combo->maCombo}", ['soLuong' => 2])->assertConflict();
        $url = '/api/thanh-toans/'.$payment['maTT'].'/xac-nhan';
        $body = ['maGiaoDich' => $payment['maGiaoDich'], 'soTien' => 70000];
        $this->postJson($url, $body)->assertForbidden();
        $staff = $this->customer();
        NhanVien::create(['maNV' => 'NVTEST', 'hoTen' => 'Staff', 'sdt' => '0900000000', 'email' => 'staff@example.com', 'chucVu' => 'NHAN_VIEN', 'ngayVaoLam' => today(), 'trangThai' => 'DANG_LAM']);
        $staff->update(['vaiTro' => 'NHAN_VIEN', 'maNV' => 'NVTEST']);
        Sanctum::actingAs($staff);
        $this->postJson($url, [...$body, 'soTien' => 1])->assertUnprocessable();
        $this->postJson($url, $body)->assertOk();
        $this->assertDatabaseHas('don_hangs', ['maDonHang' => $order->maDonHang, 'maNV' => 'NVTEST', 'trangThai' => 'DA_THANH_TOAN']);
    }

    public function test_expired_payments_cannot_succeed_and_mock_is_disabled_by_default(): void
    {
        $this->freezeTime();
        $user = $this->customer();
        $order = $this->order($user);
        Sanctum::actingAs($user);
        $url = "/api/don-hangs/{$order->maDonHang}/thanh-toan";
        $this->postJson($url, ['phuongThuc' => 'GIA_LAP'])->assertNotFound();
        config(['payments.mock_enabled' => true]);
        $this->postJson($url, ['phuongThuc' => 'GIA_LAP'])->assertUnprocessable();
        $combo = $this->combo();
        $this->postJson("/api/don-hangs/{$order->maDonHang}/combos", ['maCombo' => $combo->maCombo, 'soLuong' => 1])->assertCreated();
        $payment = $this->postJson($url, ['phuongThuc' => 'GIA_LAP'])->assertCreated()->json('data');
        $this->travel(11)->minutes();
        $this->postJson('/api/thanh-toans/'.$payment['maTT'].'/gia-lap', ['maGiaoDich' => $payment['maGiaoDich'], 'ketQua' => 'THANH_CONG'])->assertConflict();
        $this->assertDatabaseHas('thanh_toans', ['maTT' => $payment['maTT'], 'trangThai' => 'DA_HUY']);
        $this->assertSame('HET_HAN', $order->fresh()->trangThai);
    }

    public function test_failed_mock_can_retry_but_stale_attempt_cannot_confirm(): void
    {
        config(['payments.mock_enabled' => true]);
        $user = $this->customer();
        $order = $this->order($user);
        $combo = $this->combo();
        Sanctum::actingAs($user);
        $this->postJson("/api/don-hangs/{$order->maDonHang}/combos", ['maCombo' => $combo->maCombo, 'soLuong' => 1])->assertCreated();
        $url = "/api/don-hangs/{$order->maDonHang}/thanh-toan";
        $payment = $this->postJson($url, ['phuongThuc' => 'GIA_LAP'])->assertCreated()->json('data');
        $finish = '/api/thanh-toans/'.$payment['maTT'].'/gia-lap';
        $this->postJson($finish, ['maGiaoDich' => $payment['maGiaoDich'], 'ketQua' => 'THAT_BAI'])->assertOk();
        $new = $this->postJson($url, ['phuongThuc' => 'GIA_LAP'])->assertCreated()->json('data');
        $this->postJson($finish, ['maGiaoDich' => $payment['maGiaoDich'], 'ketQua' => 'THANH_CONG'])->assertConflict();
        $this->postJson($finish, ['maGiaoDich' => $new['maGiaoDich'], 'ketQua' => 'THANH_CONG'])->assertOk();
        $this->app->instance('env', 'production');
        $this->postJson($finish, ['maGiaoDich' => $new['maGiaoDich'], 'ketQua' => 'THANH_CONG'])->assertNotFound();
    }
}
