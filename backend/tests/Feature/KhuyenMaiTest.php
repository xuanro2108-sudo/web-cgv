<?php

namespace Tests\Feature;

use App\Models\KhuyenMai;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KhuyenMaiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_apply_preview_remove_and_recalculate_discount(): void
    {
        $this->freezeTime();
        $user = $this->customer();
        $order = $this->order($user);
        $combo = $this->combo();
        KhuyenMai::create(['maKM' => 'SALE10', 'tenKM' => 'Sale', 'hinhThuc' => 'GIAM_PHAN_TRAM', 'giaTri' => 10, 'donToiThieu' => 100000, 'ngayBatDau' => today(), 'ngayKetThuc' => today(), 'trangThai' => 'HOAT_DONG']);
        Sanctum::actingAs($user);
        $root = "/api/don-hangs/{$order->maDonHang}";
        $this->postJson($root.'/combos', ['maCombo' => $combo->maCombo, 'soLuong' => 2])->assertCreated();
        $this->postJson($root.'/khuyen-mai/kiem-tra', ['maKM' => 'SALE10'])->assertOk()->assertJsonPath('data.tongTien', 126000);
        $this->assertNull($order->fresh()->maKM);
        $this->postJson($root.'/khuyen-mai', ['maKM' => 'SALE10'])->assertOk();
        $this->assertSame('126000.00', $order->fresh()->tongTien);
        $this->patchJson($root.'/combos/'.$combo->maCombo, ['soLuong' => 1])->assertOk()->assertJsonPath('data.tongTien', '70000.00');
        $this->postJson($root.'/khuyen-mai', ['maKM' => 'SALE10'])->assertUnprocessable();
        $this->deleteJson($root.'/khuyen-mai')->assertOk();
        $this->assertNull($order->fresh()->maKM);
    }

    public function test_manager_validation_and_expired_codes(): void
    {
        $user = $this->customer();
        Sanctum::actingAs($user);
        $payload = ['maKM' => 'SALE', 'tenKM' => 'Sale', 'hinhThuc' => 'GIAM_PHAN_TRAM', 'giaTri' => 101, 'donToiThieu' => 0, 'ngayBatDau' => '2026-01-01', 'ngayKetThuc' => '2026-01-31'];
        $this->postJson('/api/khuyen-mais', $payload)->assertForbidden();
        $user->update(['vaiTro' => 'QUAN_LY']);
        $this->postJson('/api/khuyen-mais', $payload)->assertUnprocessable();
        $this->postJson('/api/khuyen-mais', [...$payload, 'giaTri' => 10])->assertCreated();
        $this->getJson('/api/khuyen-mais/SALE')->assertNotFound();
        $this->patchJson('/api/khuyen-mais/SALE', ['ngayKetThuc' => '2025-01-01'])->assertUnprocessable();
        $this->deleteJson('/api/khuyen-mais/SALE')->assertOk();
        $this->assertDatabaseHas('khuyen_mais', ['maKM' => 'SALE', 'trangThai' => 'NGUNG_HOAT_DONG']);
    }
}
