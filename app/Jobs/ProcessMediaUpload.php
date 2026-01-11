<?php namespace App\Jobs;

use App\Models\Media;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $mediaId)
    {
    }

    public function handle(): void
    {
        $media = Media::findOrFail($this->mediaId);

        try {
            $originalDisk     = 'public';
            $originalFullPath = Storage::disk($originalDisk)->path($media->original_path);

            $mime = mime_content_type($originalFullPath);

            if (str_starts_with($mime, 'image/')) {
                $media->update(['type' => 'image']);
                $thumbRel  = "media/thumb/{$media->id}/thumb.jpg";
                $thumbFull = Storage::disk($originalDisk)->path($thumbRel);

                if (!is_dir(dirname($thumbFull))) {
                    mkdir(dirname($thumbFull), 0775, true);
                }

                $manager = new ImageManager(new Driver());
                $img     = $manager->read($originalFullPath);

                // fit into 200x200 while keeping aspect ratio (no crop)
                $img->scaleDown(200, 200);
                $img->toJpeg(80)->save($thumbFull);

                $media->update([
                                   'thumbnail_path' => $thumbRel,
                                   'status'         => 'ready',
                                   'error_message'  => NULL,
                               ]);

                return;
            }

            if (str_starts_with($mime, 'video/')) {
                $media->update(['type' => 'video']);
                $thumbRel  = "media/thumb/{$media->id}/thumb.jpg";
                $thumbFull = Storage::disk($originalDisk)->path($thumbRel);

                if (!is_dir(dirname($thumbFull))) {
                    mkdir(dirname($thumbFull), 0775, true);
                }

                // En sam prehod: izvleče okvir in ga pomanjša na MAX 200x200 (ohrani razmerje)
                $cmd = sprintf(
                    'ffmpeg -y -ss 00:00:01 -i %s -vframes 1 -vf "scale=\'if(gt(iw,ih),min(200,iw),-2)\':\'if(gt(ih,iw),min(200,ih),-2)\'" %s 2>&1',
                    escapeshellarg($originalFullPath),
                    escapeshellarg($thumbFull)
                );

                exec($cmd, $out, $code);
                if ($code !== 0) {
                    throw new Exception('FFmpeg failed: ' . implode("\n", $out));
                }

                $media->update([
                                   'thumbnail_path' => $thumbRel,
                                   'status'         => 'ready',
                                   'error_message'  => NULL,
                               ]);

                return;
            }

            throw new Exception("Unsupported mime type: {$mime}");
        } catch (Exception $e) {
            $media->update([
                               'status'        => 'failed',
                               'error_message' => $e->getMessage(),
                           ]);
        }
    }
}