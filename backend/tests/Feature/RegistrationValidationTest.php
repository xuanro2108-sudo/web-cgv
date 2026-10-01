<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RegistrationValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith([0])]
    #[TestWith([1])]
    public function test_registration_rejects_today_and_future_birthdays(int $offset): void
    {
        $this->freezeTime();
        $this->postJson('/api/auth/register', [...$this->validData(), 'ngaySinh' => today()->addDays($offset)->toDateString()])
            ->assertUnprocessable()->assertJsonPath('errors.ngaySinh.0', 'Ngày sinh phải nhỏ hơn ngày hiện tại.');
        $this->assertDatabaseCount('khach_hangs', 0);
    }

    public function test_registration_accepts_a_past_birthday(): void
    {
        $this->freezeTime();
        $this->postJson('/api/auth/register', [...$this->validData(), 'ngaySinh' => today()->subDay()->toDateString()])->assertSuccessful();
        $this->assertDatabaseHas('khach_hangs', ['email' => 'newmember@gmail.com']);
    }

    public function test_duplicate_email_is_rejected_after_normalizing_case_and_spaces(): void
    {
        $user = $this->customer();
        $user->khachHang->update(['email' => 'existing@gmail.com']);
        $this->postJson('/api/auth/register', [...$this->validData(), 'email' => '  EXISTING@GMAIL.COM  '])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Email đã được sử dụng. Vui lòng chọn email khác.');
        $this->assertDatabaseCount('khach_hangs', 1);
    }

    private function validData(): array
    {
        return ['hoTen' => 'Test member', 'email' => 'newmember@gmail.com', 'soDienThoai' => '0912345678', 'matKhau' => 'password123', 'matKhau_confirmation' => 'password123'];
    }
}
