<?php

namespace Tests\Feature;

use App\Models\NhanVien;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class HoSoKhachHangTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_internal_password_change_checks_current_password_and_revokes_tokens(): void
    {
        $user = $this->customer();
        $user->update(['vaiTro' => 'QUAN_LY']);
        $token = $user->createToken('internal')->plainTextToken;
        $user->createToken('other');
        $body = ['matKhauHienTai' => 'wrong', 'matKhauMoi' => 'newPassword123', 'matKhauMoi_confirmation' => 'newPassword123'];
        $this->withToken($token)->patchJson('/api/auth/internal/password', $body)->assertUnprocessable()
            ->assertJsonPath('errors.matKhauHienTai.0', 'Mật khẩu hiện tại không đúng.');
        $this->assertSame(2, $user->tokens()->count());
        $this->withToken($token)->patchJson('/api/auth/internal/password', [...$body, 'matKhauHienTai' => 'password123', 'matKhauMoi_confirmation' => 'different'])->assertUnprocessable();
        $this->withToken($token)->patchJson('/api/auth/internal/password', [...$body, 'matKhauHienTai' => 'password123'])->assertOk();
        $this->assertTrue(Hash::check('newPassword123', $user->fresh()->matKhau));
        $this->assertSame(0, $user->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_customers_cannot_change_password_through_internal_endpoint(): void
    {
        Sanctum::actingAs($this->customer());
        $this->patchJson('/api/auth/internal/password', [])->assertForbidden();
    }

    public function test_internal_profile_updates_own_details_without_changing_privileges(): void
    {
        $user = $this->customer();
        $employee = NhanVien::create(['maNV' => 'SELF', 'hoTen' => 'Employee', 'sdt' => '0912345678', 'email' => 'employee@gmail.com', 'chucVu' => 'NHAN_VIEN', 'ngayVaoLam' => today(), 'trangThai' => 'DANG_LAM']);
        $user->update(['vaiTro' => 'NHAN_VIEN', 'maNV' => $employee->maNV]);
        Sanctum::actingAs($user);
        $this->patchJson('/api/auth/internal/profile', ['hoTen' => 'Updated', 'sdt' => '0987654321', 'email' => 'UPDATED@gmail.com', 'vaiTro' => 'QUAN_LY', 'maNV' => 'OTHER'])
            ->assertOk()->assertJsonPath('taiKhoan.nhan_vien.hoTen', 'Updated')->assertJsonMissingPath('taiKhoan.matKhau');
        $this->assertDatabaseHas('tai_khoans', ['maTK' => $user->maTK, 'tenDangNhap' => 'updated@gmail.com', 'vaiTro' => 'NHAN_VIEN', 'maNV' => 'SELF']);
        $this->assertDatabaseHas('nhan_viens', ['maNV' => 'SELF', 'sdt' => '0987654321', 'email' => 'updated@gmail.com']);
        $this->patchJson('/api/auth/internal/profile', ['hoTen' => '', 'sdt' => '123', 'email' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['hoTen', 'sdt', 'email']);
        NhanVien::create(['maNV' => 'OTHER', 'hoTen' => 'Other', 'sdt' => '0900000000', 'email' => 'other@gmail.com', 'chucVu' => 'NHAN_VIEN', 'ngayVaoLam' => today(), 'trangThai' => 'DANG_LAM']);
        $this->patchJson('/api/auth/internal/profile', ['hoTen' => 'Updated', 'sdt' => '0900000000', 'email' => 'other@gmail.com'])
            ->assertUnprocessable()->assertJsonValidationErrors(['sdt', 'email']);
    }

    public function test_customer_cannot_edit_internal_profile(): void
    {
        Sanctum::actingAs($this->customer());
        $this->patchJson('/api/auth/internal/profile', [])->assertForbidden();
    }

    #[TestWith(['hoTen', '   ', 'Vui lòng nhập họ và tên.'])]
    #[TestWith(['soDienThoai', '', 'Vui lòng nhập số điện thoại.'])]
    #[TestWith(['email', '', 'Vui lòng nhập email.'])]
    #[TestWith(['soDienThoai', '1234567890', 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.'])]
    #[TestWith(['soDienThoai', '09123abc45', 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.'])]
    #[TestWith(['soDienThoai', '091234567', 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.'])]
    #[TestWith(['soDienThoai', '09123456789', 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.'])]
    #[TestWith(['email', 'not-an-email', 'Email không đúng định dạng.'])]
    public function test_invalid_profile_fields_have_vietnamese_errors(string $field, string $value, string $message): void
    {
        $user = $this->customer();
        $before = $user->khachHang->getAttributes();
        Sanctum::actingAs($user);
        $this->patchJson('/api/ho-so', [$field => $value])->assertUnprocessable()
            ->assertJsonPath('errors.'.$field.'.0', $message);
        $this->assertSame($before, $user->khachHang->fresh()->getAttributes());
    }

    public function test_duplicate_contacts_are_rejected_but_own_contacts_are_allowed(): void
    {
        $user = $this->customer();
        $other = $this->customer();
        $user->khachHang->update(['soDienThoai' => '0912345678']);
        $other->khachHang->update(['soDienThoai' => '0987654321']);
        Sanctum::actingAs($user);
        $this->patchJson('/api/ho-so', ['soDienThoai' => '0987654321', 'email' => $other->khachHang->email])
            ->assertUnprocessable()
            ->assertJsonPath('errors.soDienThoai.0', 'Số điện thoại đã được sử dụng bởi tài khoản khác.')
            ->assertJsonPath('errors.email.0', 'Email đã được sử dụng bởi tài khoản khác.');
        $this->patchJson('/api/ho-so', ['soDienThoai' => '0912345678', 'email' => $user->khachHang->email])
            ->assertOk()->assertJsonPath('data.soDienThoai', '0912345678');
    }

    public function test_empty_passwords_have_vietnamese_errors(): void
    {
        Sanctum::actingAs($this->customer());
        $this->patchJson('/api/ho-so/mat-khau', [])->assertUnprocessable()
            ->assertJsonPath('errors.matKhauHienTai.0', 'Vui lòng nhập mật khẩu hiện tại.')
            ->assertJsonPath('errors.matKhauMoi.0', 'Vui lòng nhập mật khẩu mới.');
    }

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
