<?php

namespace Tests\Feature;

use App\Models\SanPham;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComboManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_manual_combo_code_is_required_unique_and_preserved_on_edit(): void
    {
        $this->manager();
        $product = $this->product();
        $payload = ['tenCombo' => 'Combo', 'donGia' => 50000, 'sanPhams' => [['maSP' => $product->maSP, 'soLuong' => 2]]];
        $this->postJson('/api/combos', $payload)->assertUnprocessable()->assertJsonPath('errors.maCombo.0', 'Vui lòng nhập mã combo.');
        $this->postJson('/api/combos', [...$payload, 'maCombo' => 'CB001'])->assertCreated()->assertJsonPath('data.maCombo', 'CB001');
        $this->deleteJson('/api/combos/CB001')->assertOk();
        $this->postJson('/api/combos', [...$payload, 'maCombo' => 'CB001'])->assertUnprocessable()->assertJsonPath('errors.maCombo.0', 'Mã combo đã trùng với một combo khác. Vui lòng nhập mã combo khác.');
        $this->putJson('/api/combos/CB001', ['maCombo' => 'CB002', 'tenCombo' => 'New combo'])->assertOk()->assertJsonPath('data.maCombo', 'CB001');
        $this->assertDatabaseCount('combos', 1);
        $this->assertDatabaseHas('chi_tiet_combos', ['maCombo' => 'CB001', 'maSP' => $product->maSP, 'soLuong' => 2]);
    }

    private function manager(): void
    {
        $account = $this->customer();
        $account->update(['vaiTro' => 'QUAN_LY']);
        Sanctum::actingAs($account);
    }

    private function product(): SanPham
    {
        return SanPham::create(['maSP' => 'SPTEST', 'tenSP' => 'Popcorn', 'loaiSP' => 'BAP', 'donGia' => 30000, 'trangThai' => 'HOAT_DONG']);
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('combo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    }

    public function test_manager_can_upload_edit_stop_and_reactivate_a_combo(): void
    {
        Storage::fake('public');
        $this->manager();
        $product = $this->product();
        $payload = ['maCombo' => 'CB-FAMILY', 'tenCombo' => 'Family combo', 'donGia' => 50000, 'sanPhams' => [['maSP' => $product->maSP, 'soLuong' => 2]], 'anh' => $this->image()];
        $combo = $this->postJson('/api/combos', $payload)->assertCreated()->json('data');
        $url = '/api/combos/'.$combo['maCombo'];
        Storage::disk('public')->assertExists('combo-images/'.basename($combo['hinhAnh']));
        $this->assertDatabaseHas('chi_tiet_combos', ['maCombo' => $combo['maCombo'], 'maSP' => $product->maSP, 'soLuong' => 2]);
        $this->putJson($url, ['donGia' => 60000])->assertOk()->assertJsonPath('data.hinhAnh', $combo['hinhAnh']);
        $updated = $this->postJson($url, ['_method' => 'PUT', 'sanPhams' => [['maSP' => $product->maSP, 'soLuong' => 3]], 'anh' => $this->image()])->assertOk()->json('data');
        $this->assertNotSame($combo['hinhAnh'], $updated['hinhAnh']);
        Storage::disk('public')->assertExists('combo-images/'.basename($updated['hinhAnh']));
        $this->assertDatabaseHas('chi_tiet_combos', ['maCombo' => $combo['maCombo'], 'soLuong' => 3]);
        $this->assertDatabaseCount('chi_tiet_combos', 1);
        $this->deleteJson($url)->assertOk();
        $this->getJson('/api/combos')->assertJsonCount(0, 'data');
        $this->getJson('/api/quan-ly/combos?trangThai=NGUNG_HOAT_DONG&keyword=Family')->assertOk()->assertJsonCount(1, 'data');
        $this->assertDatabaseHas('chi_tiet_combos', ['maCombo' => $combo['maCombo'], 'soLuong' => 3]);
        $this->putJson($url, ['trangThai' => 'HOAT_DONG'])->assertOk();
        $this->getJson('/api/combos')->assertJsonCount(1, 'data');
    }

    public function test_management_listing_requires_active_staff_and_writes_require_manager(): void
    {
        $this->getJson('/api/quan-ly/combos')->assertUnauthorized();
        $this->postJson('/api/combos', [])->assertUnauthorized();
        $account = $this->customer();
        Sanctum::actingAs($account);
        $this->getJson('/api/quan-ly/combos')->assertForbidden();
        $account->update(['vaiTro' => 'NHAN_VIEN']);
        $this->getJson('/api/quan-ly/combos')->assertOk();
        $this->postJson('/api/combos', [])->assertForbidden();
        $account->update(['trangThai' => 'KHOA']);
        $this->getJson('/api/quan-ly/combos')->assertForbidden();
    }

    public function test_invalid_products_and_images_leave_combo_unchanged(): void
    {
        Storage::fake('public');
        $this->manager();
        $product = $this->product();
        $combo = $this->combo();
        $url = '/api/combos/'.$combo->maCombo;
        $line = ['maSP' => $product->maSP, 'soLuong' => 1];
        $this->putJson($url, ['sanPhams' => [$line, $line]])->assertUnprocessable()->assertJsonValidationErrors('sanPhams.0.maSP');
        $product->update(['trangThai' => 'NGUNG_HOAT_DONG']);
        $this->putJson($url, ['sanPhams' => [$line]])->assertUnprocessable()->assertJsonValidationErrors('sanPhams.0.maSP');
        $this->postJson($url, ['_method' => 'PUT', 'anh' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')])->assertUnprocessable()->assertJsonValidationErrors('anh');
        $this->postJson($url, ['_method' => 'PUT', 'anh' => $this->image()->size(2049)])->assertUnprocessable()->assertJsonValidationErrors('anh');
        $this->assertDatabaseHas('combos', ['maCombo' => $combo->maCombo, 'donGia' => 70000, 'hinhAnh' => null]);
        Storage::disk('public')->assertDirectoryEmpty('combo-images');
    }

    public function test_image_is_removed_when_update_cannot_find_combo(): void
    {
        Storage::fake('public');
        $this->manager();
        $this->postJson('/api/combos/missing', ['_method' => 'PUT', 'anh' => $this->image()])->assertNotFound();
        Storage::disk('public')->assertDirectoryEmpty('combo-images');
    }
}
