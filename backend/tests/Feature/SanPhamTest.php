<?php

namespace Tests\Feature;

use App\Models\ChiTietCombo;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SanPhamTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_manager_can_manage_products_without_removing_combo_history(): void
    {
        $manager = $this->customer();
        $manager->update(['vaiTro' => 'QUAN_LY']);
        Sanctum::actingAs($manager);
        $product = $this->postJson('/api/san-phams', ['tenSP' => 'Bắp', 'loaiSP' => 'BAP', 'donGia' => 40000, 'maSP' => 'FORGED'])->assertCreated()->json('data');
        $this->assertNotSame('FORGED', $product['maSP']);
        $combo = $this->combo();
        ChiTietCombo::create(['maCTCombo' => 'COMP1', 'maCombo' => $combo->maCombo, 'maSP' => $product['maSP'], 'soLuong' => 1]);
        $url = '/api/san-phams/'.$product['maSP'];
        $this->patchJson($url, ['donGia' => 45000])->assertOk()->assertJsonPath('data.donGia', '45000.00');
        $this->getJson('/api/san-phams?loaiSP=BAP')->assertOk()->assertJsonCount(1, 'data');
        $this->deleteJson($url)->assertOk();
        $this->getJson($url)->assertNotFound();
        $this->getJson('/api/quan-ly/san-phams?trangThai=NGUNG_HOAT_DONG')->assertOk()->assertJsonCount(1, 'data');
        $this->assertDatabaseHas('chi_tiet_combos', ['maCTCombo' => 'COMP1']);
        $this->patchJson($url, ['trangThai' => 'HOAT_DONG'])->assertOk();
        $this->getJson($url)->assertOk();
    }

    public function test_writes_reject_guests_customers_and_invalid_prices(): void
    {
        $body = ['tenSP' => 'Water', 'loaiSP' => 'NUOC', 'donGia' => -1];
        $this->postJson('/api/san-phams', $body)->assertUnauthorized();
        $account = $this->customer();
        Sanctum::actingAs($account);
        $this->postJson('/api/san-phams', $body)->assertForbidden();
        $this->getJson('/api/quan-ly/san-phams')->assertForbidden();
        $account->update(['vaiTro' => 'QUAN_LY']);
        $this->postJson('/api/san-phams', $body)->assertUnprocessable();
        $this->postJson('/api/san-phams', [...$body, 'donGia' => 1.234])->assertUnprocessable();
        $account->update(['trangThai' => 'KHOA']);
        $this->postJson('/api/san-phams', [...$body, 'donGia' => 100])->assertForbidden();
        $this->assertDatabaseCount('san_phams', 0);
    }
}
