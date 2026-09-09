<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class QuanLyKhachHangTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function loginManager(): void
    {
        $manager = $this->customer();
        $manager->update(['vaiTro' => 'QUAN_LY']);
        Sanctum::actingAs($manager);
    }

    public function test_guests_receive_401_for_every_action(): void
    {
        $this->getJson('/api/quan-ly/khach-hangs')->assertUnauthorized();
        $this->getJson('/api/quan-ly/khach-hangs/KH1/giao-dich')->assertUnauthorized();
        $this->deleteJson('/api/quan-ly/khach-hangs/KH1')->assertUnauthorized();
        $this->patchJson('/api/quan-ly/khach-hangs/KH1/trang-thai', ['trangThai' => 'KHOA'])->assertUnauthorized();
    }

    #[TestWith(['KHACH_HANG', 'HOAT_DONG'])]
    #[TestWith(['NHAN_VIEN', 'HOAT_DONG'])]
    #[TestWith(['QUAN_LY', 'KHOA'])]
    public function test_unprivileged_or_locked_accounts_receive_403(string $role, string $status): void
    {
        $actor = $this->customer();
        $actor->update(['vaiTro' => $role, 'trangThai' => $status]);
        $target = $this->customer();
        Sanctum::actingAs($actor);

        $this->getJson('/api/quan-ly/khach-hangs')->assertForbidden();
        $this->getJson("/api/quan-ly/khach-hangs/{$target->maKH}/giao-dich")->assertForbidden();
        $this->deleteJson("/api/quan-ly/khach-hangs/{$target->maKH}")->assertForbidden();
        $this->patchJson("/api/quan-ly/khach-hangs/{$target->maKH}/trang-thai", ['trangThai' => 'KHOA'])->assertForbidden();
        $this->assertModelExists($target);
        $this->assertSame('HOAT_DONG', $target->fresh()->trangThai);
    }

    public function test_manager_searches_customer_fields_filters_status_and_receives_no_password(): void
    {
        $this->loginManager();
        $customer = $this->customer();
        $customer->update(['tenDangNhap' => 'unique_login', 'trangThai' => 'KHOA']);
        $customer->khachHang->update(['hoTen' => 'Unique Name', 'email' => 'unique@example.com', 'soDienThoai' => '0901234567']);
        $this->customer();

        foreach (['unique_login', 'Unique Name', 'unique@example.com', '0901234567', $customer->maKH] as $term) {
            $this->getJson('/api/quan-ly/khach-hangs?'.http_build_query(['q' => $term, 'trangThai' => 'KHOA']))
                ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.maKH', $customer->maKH)
                ->assertJsonPath('stats.total', 2)->assertJsonPath('stats.active', 1)->assertJsonPath('stats.locked', 1)
                ->assertJsonMissingPath('data.0.matKhau');
        }
        $this->getJson('/api/quan-ly/khach-hangs?q=unique_login&trangThai=HOAT_DONG')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/quan-ly/khach-hangs?q=%25')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/quan-ly/khach-hangs?q=does-not-exist')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_customer_list_is_paginated_and_profile_lock_is_respected(): void
    {
        $this->loginManager();
        for ($index = 0; $index < 6; $index++) {
            $this->customer();
        }
        $locked = $this->customer();
        $locked->khachHang->update(['trangThai' => 'KHOA']);

        $this->getJson('/api/quan-ly/khach-hangs?page=2')->assertOk()->assertJsonPath('total', 7)->assertJsonCount(2, 'data');
        $this->getJson('/api/quan-ly/khach-hangs?trangThai=KHOA')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.maKH', $locked->maKH);
    }

    #[TestWith(['page=0', 'page'])]
    #[TestWith(['page=abc', 'page'])]
    #[TestWith(['trangThai=invalid', 'trangThai'])]
    #[TestWith(['q[]=invalid', 'q'])]
    public function test_invalid_filters_receive_422(string $query, string $field): void
    {
        $this->loginManager();
        $this->getJson('/api/quan-ly/khach-hangs?'.$query)->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    public function test_history_is_scoped_paginated_and_sums_only_paid_orders(): void
    {
        $this->loginManager();
        $customer = $this->customer();
        $other = $this->customer();
        $this->order($other)->update(['trangThai' => 'DA_THANH_TOAN', 'tongTien' => 900000]);
        $this->order($customer)->update(['trangThai' => 'DA_THANH_TOAN', 'tongTien' => 150000]);
        for ($index = 0; $index < 10; $index++) {
            $this->order($customer)->update(['trangThai' => 'DA_HUY', 'tongTien' => 50000]);
        }

        $this->getJson("/api/quan-ly/khach-hangs/{$customer->maKH}/giao-dich")
            ->assertOk()->assertJsonPath('total', 11)->assertJsonPath('paidTotal', 150000)->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.ve_ghes_count', 0)->assertJsonMissingPath('data.0.maQR');
        $this->getJson("/api/quan-ly/khach-hangs/{$customer->maKH}/giao-dich?page=2")->assertOk()->assertJsonCount(1, 'data');
        $empty = $this->customer();
        $this->getJson("/api/quan-ly/khach-hangs/{$empty->maKH}/giao-dich")->assertOk()->assertJsonPath('total', 0)->assertJsonPath('paidTotal', 0);
        $this->getJson('/api/quan-ly/khach-hangs/missing/giao-dich')->assertNotFound();
        $this->getJson("/api/quan-ly/khach-hangs/{$customer->maKH}/giao-dich?page=0")->assertUnprocessable();
    }

    public function test_delete_revokes_tokens_preserves_orders_and_removes_account_from_list(): void
    {
        $this->loginManager();
        $customer = $this->customer();
        $order = $this->order($customer);
        $order->update(['trangThai' => 'DA_THANH_TOAN', 'tongTien' => 150000]);
        $customer->createToken('device-one');
        $customer->createToken('device-two');

        $this->deleteJson("/api/quan-ly/khach-hangs/{$customer->maKH}")->assertOk();

        $this->assertModelMissing($customer);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $customer->maTK]);
        $this->assertDatabaseHas('khach_hangs', ['maKH' => $customer->maKH, 'trangThai' => 'DA_XOA']);
        $this->assertDatabaseHas('don_hangs', ['maDonHang' => $order->maDonHang, 'trangThai' => 'DA_THANH_TOAN', 'tongTien' => 150000]);
        $this->getJson('/api/quan-ly/khach-hangs')->assertOk()->assertJsonPath('total', 0);
        $this->getJson("/api/quan-ly/khach-hangs/{$customer->maKH}/giao-dich")->assertOk()->assertJsonPath('total', 1);
        $this->deleteJson("/api/quan-ly/khach-hangs/{$customer->maKH}")->assertNotFound();
    }

    public function test_manager_locks_and_unlocks_account_without_losing_orders(): void
    {
        $this->loginManager();
        $customer = $this->customer();
        $order = $this->order($customer);
        $customer->createToken('device-one');
        $customer->createToken('device-two');
        $path = "/api/quan-ly/khach-hangs/{$customer->maKH}/trang-thai";

        $this->patchJson($path, ['trangThai' => 'KHOA', 'vaiTro' => 'QUAN_LY'])
            ->assertOk()->assertJsonPath('trangThai', 'KHOA');
        $this->assertSame('KHOA', $customer->fresh()->trangThai);
        $this->assertSame('KHOA', $customer->khachHang->fresh()->trangThai);
        $this->assertSame('KHACH_HANG', $customer->fresh()->vaiTro);
        $this->assertSame(0, $customer->tokens()->count());
        $this->assertModelExists($order);

        $this->patchJson($path, ['trangThai' => 'HOAT_DONG'])->assertOk()->assertJsonPath('trangThai', 'HOAT_DONG');
        $this->assertSame('HOAT_DONG', $customer->fresh()->trangThai);
        $this->assertSame('HOAT_DONG', $customer->khachHang->fresh()->trangThai);
        $this->assertSame(0, $customer->tokens()->count());
    }

    public function test_status_rejects_invalid_values_missing_and_internal_accounts(): void
    {
        $this->loginManager();
        $customer = $this->customer();
        $path = "/api/quan-ly/khach-hangs/{$customer->maKH}/trang-thai";
        $this->patchJson($path, [])->assertUnprocessable()->assertJsonValidationErrors('trangThai');
        $this->patchJson($path, ['trangThai' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('trangThai');
        $this->assertSame('HOAT_DONG', $customer->fresh()->trangThai);
        $customer->update(['vaiTro' => 'NHAN_VIEN']);
        $this->patchJson($path, ['trangThai' => 'KHOA'])->assertNotFound();
        $this->assertSame('HOAT_DONG', $customer->khachHang->fresh()->trangThai);
        $this->patchJson('/api/quan-ly/khach-hangs/missing/trang-thai', ['trangThai' => 'KHOA'])->assertNotFound();
    }

    public function test_delete_cannot_remove_internal_accounts(): void
    {
        $this->loginManager();
        $staff = $this->customer();
        $staff->update(['vaiTro' => 'NHAN_VIEN']);

        $this->deleteJson("/api/quan-ly/khach-hangs/{$staff->maKH}")->assertNotFound();
        $this->assertModelExists($staff);
        $this->assertSame('HOAT_DONG', $staff->khachHang->fresh()->trangThai);
        $this->deleteJson('/api/quan-ly/khach-hangs/missing')->assertNotFound();
    }
}
