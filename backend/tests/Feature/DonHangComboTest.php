<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DonHangComboTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_add_change_and_remove_combo_with_server_prices(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        $combo = $this->combo();
        Sanctum::actingAs($user);
        $url = "/api/don-hangs/{$order->maDonHang}/combos";
        $this->postJson($url, ['maCombo' => $combo->maCombo, 'soLuong' => 2, 'donGia' => 1])->assertCreated()->assertJsonPath('data.tongTien', '140000.00');
        $this->postJson($url, ['maCombo' => $combo->maCombo, 'soLuong' => 1])->assertConflict();
        $combo->update(['donGia' => 90000]);
        $this->patchJson($url.'/'.$combo->maCombo, ['soLuong' => 3])->assertOk()->assertJsonPath('data.tongTien', '210000.00');
        $this->assertDatabaseHas('chi_tiet_combo_don_hangs', ['maDonHang' => $order->maDonHang, 'donGia' => 70000, 'soLuong' => 3]);
        $this->deleteJson($url.'/'.$combo->maCombo)->assertOk()->assertJsonPath('data.tongTien', '0.00');
        $this->assertDatabaseMissing('chi_tiet_combo_don_hangs', ['maDonHang' => $order->maDonHang]);
    }

    public function test_rejects_guests_other_owners_invalid_quantity_and_closed_orders(): void
    {
        $owner = $this->customer();
        $order = $this->order($owner);
        $combo = $this->combo();
        $url = "/api/don-hangs/{$order->maDonHang}/combos";
        $body = ['maCombo' => $combo->maCombo, 'soLuong' => 1];
        $this->postJson($url, $body)->assertUnauthorized();
        Sanctum::actingAs($this->customer());
        $this->postJson($url, $body)->assertNotFound();
        Sanctum::actingAs($owner);
        $this->postJson($url, [...$body, 'soLuong' => 0])->assertUnprocessable();
        $combo->update(['trangThai' => 'NGUNG_HOAT_DONG']);
        $this->postJson($url, $body)->assertUnprocessable();
        $order->update(['trangThai' => 'DA_HUY']);
        $this->postJson($url, $body)->assertConflict();
        $this->assertDatabaseCount('chi_tiet_combo_don_hangs', 0);
    }
}
