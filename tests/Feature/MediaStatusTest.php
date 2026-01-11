<?php namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MediaStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_requires_authentication(): void
    {
        $media = Media::factory()->create();

        $this->getJson("/api/media/{$media->id}/status")
             ->assertStatus(401);
    }

    public function test_status_returns_404_for_non_existing_media(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['media:status']);

        $this->getJson('/api/media/999999/status')
             ->assertStatus(404);
    }

    public function test_status_returns_404_for_media_not_owned_by_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $media = Media::factory()->create([
                                              'uploaded_by' => $owner->id,
                                          ]);

        Sanctum::actingAs($other, ['media:status']);

        $this->getJson("/api/media/{$media->id}/status")
             ->assertStatus(404);
    }
}
