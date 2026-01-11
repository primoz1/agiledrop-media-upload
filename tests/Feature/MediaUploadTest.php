<?php namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Jobs\ProcessMediaUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_returns_202_and_dispatches_processing_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user, ['media:upload']);

        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $response = $this->postJson('/api/media', [
            'title'       => 'Test',
            'description' => 'Desc',
            'file'        => $file,
        ]);

        $response->assertStatus(202)
                 ->assertJsonStructure(['data' => ['id', 'status', 'status_url']]);

        $mediaId = $response->json('data.id');

        $this->assertDatabaseHas('media', [
            'id'    => $mediaId,
            'title' => 'Test',
        ]);

        Queue::assertPushed(ProcessMediaUpload::class, function ($job) use ($mediaId) {
            return $job->mediaId === $mediaId;
        });
    }

    public function test_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $this->postJson('/api/media', [
            'title' => 'Test',
            'file' => $file,
        ])->assertStatus(401);
    }

    public function test_upload_validation_fails_without_required_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['media:upload']);

        $this->postJson('/api/media', [
            'description' => 'No title and no file',
        ])->assertStatus(422)
             ->assertJsonValidationErrors(['title', 'file']);
    }

    public function test_upload_rejects_unsupported_file_type(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['media:upload']);

        $file = UploadedFile::fake()->create('test.pdf', 10, 'application/pdf');

        $this->postJson('/api/media', [
            'title' => 'Test',
            'file' => $file,
        ])->assertStatus(422);
    }
}
