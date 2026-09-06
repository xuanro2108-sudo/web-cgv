<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HoSoKhachHangTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_can_edit_only_own_profile_and_not_privileges(): void
    {
        $user = $this->customer();
        $other = $this->customer();
        Sanctum::actingAs($user);
        $this->getJson('/api/ho-so')->assertOk()->assertJsonPath('data.maKH', $user->maKH)->assertJsonMissingPath('data.matKhau');
        $this->patchJson('/api/ho-so', ['hoTen' => 'Tên mới', 'maKH' => $other->maKH, 'vaiTro' => 'QUAN_LY', 'trangThai' => 'KHOA'])->assertOk()->assertJsonPath('data.hoTen', 'Tên mới');
        $this->assertSame('KHACH_HANG', $user->fresh()->vaiTro);
        $this->assertSame('Test customer', $other->khachHang->hoTen);
        $this->patchJson('/api/ho-so', ['email' => $other->khachHang->email])->assertUnprocessable();
        $this->patchJson('/api/ho-so', ['ngaySinh' => today()->addDay()->format('Y-m-d')])->assertUnprocessable();
        $this->patchJson('/api/ho-so', ['email' => $user->khachHang->email])->assertOk();
    }

    public function test_password_change_requires_current_password_and_revokes_every_token(): void
    {
        $user = $this->customer();
        $token = $user->createToken('test')->plainTextToken;
        $user->createToken('other-device');
        $body = ['matKhauHienTai' => 'wrong', 'matKhauMoi' => 'newPassword123', 'matKhauMoi_confirmation' => 'newPassword123'];
        $this->withToken($token)->patchJson('/api/ho-so/mat-khau', $body)->assertUnprocessable();
        $this->assertSame(2, $user->tokens()->count());
        $this->withToken($token)->patchJson('/api/ho-so/mat-khau', [...$body, 'matKhauHienTai' => 'password123'])->assertOk();
        $this->assertTrue(Hash::check('newPassword123', $user->fresh()->matKhau));
        $this->assertSame(0, $user->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/ho-so')->assertUnauthorized();
    }

    public function test_profile_rejects_guests_staff_and_disabled_customers(): void
    {
        $this->getJson('/api/ho-so')->assertUnauthorized();
        $user = $this->customer();
        $user->update(['vaiTro' => 'NHAN_VIEN']);
        Sanctum::actingAs($user);
        $this->getJson('/api/ho-so')->assertForbidden();
        $user->update(['vaiTro' => 'KHACH_HANG', 'trangThai' => 'KHOA']);
        $this->getJson('/api/ho-so')->assertForbidden();
    }
}
