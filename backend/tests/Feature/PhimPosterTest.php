<?php

namespace Tests\Feature;

use App\Models\Phim;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhimPosterTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function poster(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('poster.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    }

    private function manager(): void
    {
        $user = $this->customer();
        $user->update(['vaiTro' => 'QUAN_LY']);
        Sanctum::actingAs($user);
    }

    public function test_uploaded_poster_is_saved_and_retained_until_replaced(): void
    {
        Storage::fake('public');
        $this->manager();
        $response = $this->postJson('/api/phims', ['maPhim' => 'POSTER1', 'tenPhim' => 'Poster film', 'trangThai' => 'SAP_CHIEU', 'anh' => $this->poster()])->assertCreated();
        $original = $response->json('data.hinhAnh');
        $this->assertStringContainsString('/storage/phim-posters/', $original);
        Storage::disk('public')->assertExists('phim-posters/'.basename($original));
        $this->assertDatabaseHas('phims', ['maPhim' => 'POSTER1', 'hinhAnh' => $original]);
        $this->putJson('/api/phims/POSTER1', ['tenPhim' => 'Updated title'])->assertOk()->assertJsonPath('data.hinhAnh', $original);
        $updated = $this->postJson('/api/phims/POSTER1', ['_method' => 'PUT', 'anh' => $this->poster()])->assertOk()->json('data.hinhAnh');
        $this->assertNotSame($original, $updated);
        Storage::disk('public')->assertExists('phim-posters/'.basename($updated));
        $this->assertDatabaseHas('phims', ['maPhim' => 'POSTER1', 'hinhAnh' => $updated]);
    }

    public function test_invalid_and_oversized_uploads_do_not_replace_the_poster(): void
    {
        Storage::fake('public');
        $this->manager();
        Phim::create(['maPhim' => 'POSTER2', 'tenPhim' => 'Film', 'hinhAnh' => 'old.jpg']);
        $this->postJson('/api/phims/POSTER2', ['_method' => 'PUT', 'anh' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')])->assertUnprocessable()->assertJsonValidationErrors('anh');
        $this->postJson('/api/phims/POSTER2', ['_method' => 'PUT', 'anh' => $this->poster()->size(2049)])->assertUnprocessable()->assertJsonValidationErrors('anh');
        $this->assertDatabaseHas('phims', ['maPhim' => 'POSTER2', 'hinhAnh' => 'old.jpg']);
        Storage::disk('public')->assertDirectoryEmpty('phim-posters');
    }

    public function test_guests_and_customers_cannot_upload_posters(): void
    {
        Storage::fake('public');
        $payload = ['maPhim' => 'POSTER3', 'tenPhim' => 'Film', 'trangThai' => 'SAP_CHIEU', 'anh' => $this->poster()];
        $this->postJson('/api/phims', $payload)->assertUnauthorized();
        Sanctum::actingAs($this->customer());
        $this->postJson('/api/phims', $payload)->assertForbidden();
        Storage::disk('public')->assertDirectoryEmpty('phim-posters');
        $this->assertDatabaseMissing('phims', ['maPhim' => 'POSTER3']);
    }
}
