<?php namespace App\Jobs;

use App\Enum\MediaStatus;
use App\Models\Media;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\Process\Process;

class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 3;
    public $timeout = 120;

    public function __construct(public int $mediaId)
    {
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(): void
    {
        $media = Media::findOrFail($this->mediaId);
        $disk  = 'public';

        try {
            $media->update([
                               'status' => MediaStatus::Processing
                           ]);

            $fullPath      = Storage::disk($disk)->path($media->original_path);
            $mime          = mime_content_type($fullPath);
            $thumbRelPath  = "media/thumb/{$media->id}/thumb.jpg";
            $thumbFullPath = Storage::disk($disk)->path($thumbRelPath);

            $this->ensureDirectoryExists(dirname($thumbFullPath));

            if (str_starts_with($mime, 'image/')) {
                $this->processImage($media, $fullPath, $thumbFullPath);
            } elseif (str_starts_with($mime, 'video/')) {
                $this->processVideo($media, $fullPath, $thumbFullPath);
            } else {
                throw new Exception("Unsupported mime type: {$mime}");
            }

            $media->update([
                               'thumbnail_path' => $thumbRelPath,
                               'status'         => MediaStatus::Ready,
                               'error_message'  => NULL,
                           ]);
        } catch (Exception $e) {
            $media->update([
                               'status'        => MediaStatus::Failed,
                               'error_message' => $e->getMessage(),
                           ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        // Called after all retries are exhausted
        Media::where('id', $this->mediaId)->update([
                                                       'status' => MediaStatus::Failed,
                                                       'error_message' => $e->getMessage(),
                                                   ]);
    }

    private function processImage(Media $media, string $source, string $destination): void
    {
        $media->update(['type' => 'image']);
        $manager = new ImageManager(new Driver());
        $manager->read($source)
                ->scaleDown(200, 200)
                ->toJpeg(80)
                ->save($destination);
    }

    /**
     * @throws \Exception
     */
    private function processVideo(Media $media, string $source, string $destination): void
    {
        $media->update(['type' => 'video']);

        $cmd = [
            'ffmpeg', '-y',
            '-ss', '00:00:01',
            '-i', $source,
            '-vframes', '1',
            '-vf', "scale='if(gt(iw,ih),min(200,iw),-2)':'if(gt(ih,iw),min(200,ih),-2)'",
            $destination,
            '-hide_banner', '-loglevel', 'error'
        ];

        $process = new Process($cmd);
        $process->setTimeout(60);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new \Exception('FFmpeg failed: ' . $exception->getMessage());
        }
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }
}