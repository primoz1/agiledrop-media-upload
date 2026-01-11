<?php namespace Tests\Feature;

use App\Jobs\ProcessMediaUpload;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaProcessingJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_failed_marks_media_as_failed(): void
    {
        $media = Media::factory()->create([
                                              'status' => 'processing',
                                          ]);

        $job = new ProcessMediaUpload($media->id);

        $job->failed(new \Exception('Boom'));

        $this->assertDatabaseHas('media', [
            'id'     => $media->id,
            'status' => 'failed',
        ]);
    }
}
