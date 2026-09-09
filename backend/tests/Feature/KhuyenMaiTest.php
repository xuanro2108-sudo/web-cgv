<?php

namespace Tests\Feature;

use App\Models\KhuyenMai;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KhuyenMaiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_promotion_image_upload_replace_remove_and_validation(): void
    {
        Storage::fake('public');
        $user = $this->customer();
        $user->update(['vaiTro' => 'QUAN_LY']);
        Sanctum::actingAs($user);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $payload = ['maKM' => 'IMAGE', 'tenKM' => 'Image', 'hinhThuc' => 'GIAM_GIA', 'giaTri' => 10000, 'donToiThieu' => 0, 'ngayBatDau' => '2026-01-01', 'ngayKetThuc' => '2026-12-31'];
        $response = $this->postJson('/api/khuyen-mais', [...$payload, 'anh' => UploadedFile::fake()->createWithContent('banner.png', $png)])->assertCreated();
        $old = $response->json('data.hinhAnh');
        Storage::disk('public')->assertExists($old);
        $this->assertDatabaseHas('khuyen_mais', ['maKM' => 'IMAGE', 'hinhAnh' => $old]);
        $this->patchJson('/api/khuyen-mais/IMAGE', ['anh' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')])->assertUnprocessable();
        $this->assertSame($old, KhuyenMai::find('IMAGE')->hinhAnh);
        $response = $this->postJson('/api/khuyen-mais/IMAGE', ['_method' => 'PATCH', 'anh' => UploadedFile::fake()->createWithContent('new.png', $png)])->assertOk();
        $new = $response->json('data.hinhAnh');
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
        $this->patchJson('/api/khuyen-mais/IMAGE', ['xoaAnh' => true])->assertOk()->assertJsonPath('data.hinhAnh', null);
        Storage::disk('public')->assertMissing($new);
    }

    public function test_management_lists_inactive_and_expired_codes_with_search_and_pagination(): void
    {
        $this->getJson('/api/quan-ly/khuyen-mais')->assertUnauthorized();
        $user = $this->customer();
        Sanctum::actingAs($user);
        $this->getJson('/api/quan-ly/khuyen-mais')->assertForbidden();
        $user->update(['vaiTro' => 'NHAN_VIEN']);
        $this->getJson('/api/quan-ly/khuyen-mais')->assertForbidden();
        $user->update(['vaiTro' => 'QUAN_LY']);
        for ($index = 0; $index < 11; $index++) {
            KhuyenMai::create(['maKM' => 'OLD'.$index, 'tenKM' => 'Old promotion', 'hinhThuc' => 'GIAM_GIA', 'giaTri' => 10000, 'donToiThieu' => 0, 'ngayBatDau' => '2020-01-01', 'ngayKetThuc' => '2020-01-31', 'trangThai' => 'NGUNG_HOAT_DONG']);
        }
        $this->getJson('/api/quan-ly/khuyen-mais')->assertOk()->assertJsonPath('total', 11)->assertJsonCount(10, 'data');
        $this->getJson('/api/quan-ly/khuyen-mais?page=2')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/quan-ly/khuyen-mais?q=OLD10')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.maKM', 'OLD10')->assertJsonPath('data.0.don_hangs_count', 0);
        $this->getJson('/api/quan-ly/khuyen-mais?q=promotion&trangThai=NGUNG_HOAT_DONG')->assertOk()->assertJsonPath('total', 11);
        $this->getJson('/api/quan-ly/khuyen-mais?trangThai=HOAT_DONG')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/quan-ly/khuyen-mais?q=%25')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/quan-ly/khuyen-mais?page=0&trangThai=invalid')->assertUnprocessable()->assertJsonValidationErrors(['page', 'trangThai']);
        $user->update(['trangThai' => 'KHOA']);
        $this->getJson('/api/quan-ly/khuyen-mais')->assertForbidden();
    }

    public function test_used_promotions_allow_name_changes_but_preserve_discount_conditions_and_orders(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        $promotion = KhuyenMai::create(['maKM' => 'USED', 'tenKM' => 'Used', 'hinhThuc' => 'GIAM_GIA', 'giaTri' => 10000, 'donToiThieu' => 0, 'ngayBatDau' => '2026-01-01', 'ngayKetThuc' => '2026-12-31', 'trangThai' => 'HOAT_DONG']);
        $order->update(['maKM' => $promotion->maKM]);
        $user->update(['vaiTro' => 'QUAN_LY']);
        Sanctum::actingAs($user);
        $this->getJson('/api/quan-ly/khuyen-mais?q=USED')->assertOk()->assertJsonPath('data.0.don_hangs_count', 1);
        $this->patchJson('/api/khuyen-mais/USED', ['tenKM' => 'New name'])->assertOk()->assertJsonPath('data.tenKM', 'New name');
        $this->patchJson('/api/khuyen-mais/USED', ['giaTri' => 20000])->assertConflict();
        $this->assertSame('10000.00', $promotion->fresh()->giaTri);
        $this->deleteJson('/api/khuyen-mais/USED')->assertOk();
        $this->assertSame('NGUNG_HOAT_DONG', $promotion->fresh()->trangThai);
        $this->assertSame('USED', $order->fresh()->maKM);
    }

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
