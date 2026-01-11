<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'title'          => $this->faker->sentence(3),
            'description'    => $this->faker->sentence(),
            'uploaded_by'    => User::factory(),
            'status'         => 'queued',
            'type'           => NULL,
            'original_path'  => NULL,
            'thumbnail_path' => NULL,
            'error_message'  => NULL,
        ];
    }
}
