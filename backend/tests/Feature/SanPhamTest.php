<?php

namespace Tests\Feature;

use App\Models\ChiTietCombo;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SanPhamTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_manual_product_code_is_required_unique_and_preserved_on_edit(): void
    {
        $manager = $this->customer();
        $manager->update(['vaiTro' => 'QUAN_LY']);
        Sanctum::actingAs($manager);
        $payload = ['tenSP' => 'Water', 'loaiSP' => 'NUOC', 'donGia' => 20000];
        $this->postJson('/api/san-phams', $payload)->assertUnprocessable()->assertJsonPath('errors.maSP.0', 'Vui lòng nhập mã sản phẩm.');
        $this->postJson('/api/san-phams', [...$payload, 'maSP' => 'SP001'])->assertCreated()->assertJsonPath('data.maSP', 'SP001');
        $this->deleteJson('/api/san-phams/SP001')->assertOk();
        $this->postJson('/api/san-phams', [...$payload, 'maSP' => 'SP001'])->assertUnprocessable()->assertJsonPath('errors.maSP.0', 'Mã sản phẩm đã trùng với một sản phẩm khác. Vui lòng nhập mã sản phẩm khác.');
        $this->putJson('/api/san-phams/SP001', ['maSP' => 'SP002', 'tenSP' => 'New water'])->assertOk()->assertJsonPath('data.maSP', 'SP001');
        $this->assertDatabaseCount('san_phams', 1);
        $this->assertDatabaseHas('san_phams', ['maSP' => 'SP001', 'tenSP' => 'New water']);
    }

    public function test_product_image_can_be_uploaded_replaced_and_used_in_a_combo(): void
    {
        Storage::fake('public');
        $manager = $this->customer();
        $manager->update(['vaiTro' => 'QUAN_LY']);
        Sanctum::actingAs($manager);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $product = $this->postJson('/api/san-phams', ['maSP' => 'SP-IMAGE', 'tenSP' => 'Popcorn', 'loaiSP' => 'BAP', 'donGia' => 30000, 'anh' => UploadedFile::fake()->createWithContent('product.png', $png)])->assertCreated()->json('data');
        $url = '/api/san-phams/'.$product['maSP'];
        Storage::disk('public')->assertExists('product-images/'.basename($product['hinhAnh']));
        $this->assertDatabaseHas('san_phams', ['maSP' => $product['maSP'], 'hinhAnh' => $product['hinhAnh']]);
        $this->putJson($url, ['tenSP' => 'New popcorn'])->assertOk()->assertJsonPath('data.hinhAnh', $product['hinhAnh']);
        $new = $this->postJson($url, ['_method' => 'PUT', 'anh' => UploadedFile::fake()->createWithContent('new.png', $png)])->assertOk()->json('data.hinhAnh');
        $this->assertNotSame($product['hinhAnh'], $new);
        Storage::disk('public')->assertExists('product-images/'.basename($new));
        $this->getJson('/api/quan-ly/san-phams?keyword=New&loaiSP=BAP&trangThai=HOAT_DONG')->assertOk()->assertJsonCount(1, 'data');
        $combo = $this->postJson('/api/combos', ['maCombo' => 'CB-IMAGE', 'tenCombo' => 'Popcorn combo', 'donGia' => 50000, 'sanPhams' => [['maSP' => $product['maSP'], 'soLuong' => 2]]])->assertCreated()->json('data');
        $this->assertDatabaseHas('chi_tiet_combos', ['maCombo' => $combo['maCombo'], 'maSP' => $product['maSP'], 'soLuong' => 2]);
        $this->postJson($url, ['_method' => 'PUT', 'anh' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')])->assertUnprocessable()->assertJsonValidationErrors('anh');
        $this->postJson($url, ['_method' => 'PUT', 'anh' => UploadedFile::fake()->createWithContent('large.png', $png)->size(2049)])->assertUnprocessable()->assertJsonPath('errors.anh.0', 'Ảnh không được lớn hơn 2 MB.');
        $this->assertDatabaseHas('san_phams', ['maSP' => $product['maSP'], 'hinhAnh' => $new]);
    }

    public function test_upload_is_cleaned_up_when_product_does_not_exist(): void
    {
        Storage::fake('public');
        $manager = $this->customer();
        $manager->update(['vaiTro' => 'QUAN_LY']);
        Sanctum::actingAs($manager);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $this->postJson('/api/san-phams/missing', ['_method' => 'PUT', 'anh' => UploadedFile::fake()->createWithContent('new.png', $png)])->assertNotFound();
        Storage::disk('public')->assertDirectoryEmpty('product-images');
    }

    public function test_manager_can_manage_products_without_removing_combo_history(): void
    {
        $manager = $this->customer();
        $manager->update(['vaiTro' => 'QUAN_LY']);
        Sanctum::actingAs($manager);
        $product = $this->postJson('/api/san-phams', ['tenSP' => 'Bắp', 'loaiSP' => 'BAP', 'donGia' => 40000, 'maSP' => 'SP-MANUAL'])->assertCreated()->json('data');
        $this->assertSame('SP-MANUAL', $product['maSP']);
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
        $body = ['maSP' => 'SP-WATER', 'tenSP' => 'Water', 'loaiSP' => 'NUOC', 'donGia' => -1];
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
