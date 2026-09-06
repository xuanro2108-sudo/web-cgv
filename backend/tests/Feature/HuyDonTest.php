<?php

namespace Tests\Feature;

use App\Models\Ghe;
use App\Models\VeGhe;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HuyDonTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_cancel_releases_seat_and_keeps_ticket_history(): void
    {
        $this->freezeTime();
        $user = $this->customer();
        $order = $this->order($user);
        $this->seat();
        Sanctum::actingAs($user);
        $body = ['maDonHang' => $order->maDonHang, 'maLichChieu' => 'L1', 'maGhe' => 'G1'];
        $response = $this->postJson('/api/ve-ghes', $body)->assertCreated();
        $id = $response->json('data.0.maVe');
        $this->postJson('/api/ve-ghes', $body)->assertConflict();
        $this->patchJson('/api/ve-ghes/'.$id, ['trangThai' => 'DA_DAT'])->assertUnprocessable();
        $this->patchJson("/api/don-hangs/{$order->maDonHang}/huy")->assertOk()->assertJsonPath('data.trangThai', 'DA_HUY');
        $next = $this->order($user);
        $this->postJson('/api/ve-ghes', [...$body, 'maDonHang' => $next->maDonHang])->assertCreated();
        $this->assertDatabaseHas('ve_ghes', ['maVe' => $id, 'trangThai' => 'DA_HUY']);
        $this->assertDatabaseCount('ve_ghes', 2);
    }

    public function test_expiry_blocks_edits_and_command_releases_holds_idempotently(): void
    {
        $this->freezeTime();
        $user = $this->customer();
        $order = $this->order($user);
        $combo = $this->combo();
        $this->seat();
        Sanctum::actingAs($user);
        $this->postJson('/api/ve-ghes', ['maDonHang' => $order->maDonHang, 'maLichChieu' => 'L1', 'maGhe' => 'G1'])->assertCreated();
        $this->travel(11)->minutes();
        $this->postJson("/api/don-hangs/{$order->maDonHang}/combos", ['maCombo' => $combo->maCombo, 'soLuong' => 1])->assertConflict();
        $this->artisan('orders:expire')->assertSuccessful();
        $this->artisan('orders:expire')->assertSuccessful();
        $this->assertDatabaseHas('don_hangs', ['maDonHang' => $order->maDonHang, 'trangThai' => 'HET_HAN']);
        $this->assertDatabaseHas('ve_ghes', ['maDonHang' => $order->maDonHang, 'trangThai' => 'DA_HUY']);
    }

    public function test_ownership_and_paid_order_cancellation_are_enforced(): void
    {
        $owner = $this->customer();
        $order = $this->order($owner);
        Sanctum::actingAs($this->customer());
        $this->patchJson("/api/don-hangs/{$order->maDonHang}/huy")->assertNotFound();
        Sanctum::actingAs($owner);
        $order->update(['trangThai' => 'DA_THANH_TOAN']);
        $this->patchJson("/api/don-hangs/{$order->maDonHang}/huy")->assertConflict();
    }

    public function test_double_seats_are_held_and_released_together(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        $seat = $this->seat();
        $seat->update(['loaiGhe' => 'DOI']);
        Ghe::create(['maGhe' => 'G2', 'maSoDo' => 'S1', 'hang' => 'A', 'cot' => 2, 'loaiGhe' => 'DOI', 'trangThai' => 'HOAT_DONG']);
        Sanctum::actingAs($user);
        $result = $this->postJson('/api/ve-ghes', ['maDonHang' => $order->maDonHang, 'maLichChieu' => 'L1', 'maGhe' => 'G1'])->assertCreated()->assertJsonCount(2, 'data');
        $this->assertSame('240000.00', $order->fresh()->tongTien);
        $this->patchJson('/api/ve-ghes/'.$result->json('data.0.maVe'), ['trangThai' => 'DA_HUY'])->assertOk()->assertJsonPath('data.tongTien', '0.00');
        $this->assertSame(0, VeGhe::where('trangThai', 'GIU_CHO')->count());
    }
}
