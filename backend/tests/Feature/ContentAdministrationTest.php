<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_content_writes_require_staff_access(): void
    {
        $payload = [
            'maPhim' => 'P1',
            'tenPhim' => 'Film',
            'trangThai' => 'SAP_CHIEU',
        ];

        $this->postJson('/api/phims', $payload)->assertUnauthorized();

        Sanctum::actingAs($this->customer());

        $this->postJson('/api/phims', $payload)->assertForbidden();
    }

    public function test_manager_can_create_content(): void
    {
        $manager = $this->customer();
        $manager->update(['vaiTro' => 'QUAN_LY']);
        Sanctum::actingAs($manager);

        $this->postJson('/api/phims', [
            'maPhim' => 'P1',
            'tenPhim' => 'Film',
            'trangThai' => 'SAP_CHIEU',
        ])->assertCreated();
    }

    public function test_schedule_update_rejects_end_time_before_start_time(): void
    {
        $this->seat();
        $manager = $this->customer();
        $manager->update(['vaiTro' => 'QUAN_LY']);
        Sanctum::actingAs($manager);

        $this->patchJson('/api/lich-chieus/L1', [
            'gioBatDau' => '18:00',
            'gioKetThuc' => '17:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['gioKetThuc']);
    }
}
