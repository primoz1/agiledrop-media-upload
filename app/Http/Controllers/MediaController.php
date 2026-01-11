<?php namespace App\Http\Controllers;

use App\Enum\MediaStatus;
use App\Http\Requests\GetMediaStatusRequest;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Jobs\ProcessMediaUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Store a new media upload and dispatch a processing job.
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $user = $request->user();
        $path = NULL;

        return DB::transaction(function () use ($request, $user, &$path) {
            try {
                // Initialize media record using relationship for automatic user association
                $media = $user->media()->create([
                                                    'title'       => $request->validated('title'),
                                                    'description' => $request->validated('description'),
                                                    'status'      => MediaStatus::Queued,
                                                    'client_id'   => $user->client_id,
                                                ]);

                // Store the file using a predictable name within the media ID folder
                $file      = $request->file('file');
                $extension = $file->getClientOriginalExtension();
                $fileName  = "original.{$extension}";

                // TODO: For production usage, consider storing uploads in a temporary location
                // and moving them to the final destination inside the processing job.
                // This would further reduce request time and improve fault tolerance.
                $path = $file->storeAs(
                    "media/original/{$media->id}",
                    $fileName,
                    'public'
                );

                if (!$path) {
                    // Explicit failure if storage returns false/null
                    $media->update([
                                       'status'        => MediaStatus::Failed,
                                       'error_message' => 'Failed to store uploaded file.',
                                   ]);
                    DB::commit();

                    return response()->json([
                                                'message' => 'Upload failed.',
                                            ], 500);
                }

                // Link the stored file path to the media record
                $media->update(['original_path' => $path]);

                // Dispatch the processing job after the transaction is committed
                ProcessMediaUpload::dispatch($media->id)->afterCommit();

                return (new MediaResource($media))
                    ->response()
                    ->setStatusCode(202);

            } catch (\Throwable $e) {
                // Clean up the file if the database transaction fails
                if ($path) {
                    Storage::disk('public')->delete($path);
                }

                // If record exists, mark it failed (optional but nice)
                if ($media) {
                    $media->forceFill([
                                          'status'        => MediaStatus::Failed,
                                          'error_message' => $e->getMessage(),
                                      ])->save();
                }

                throw $e;
            }
        });
    }

    /**
     * Retrieve the current processing status of a media item.
     */
    public function status(GetMediaStatusRequest $request): JsonResponse
    {
        $media = $request->mediaOrFail();

        return (new MediaResource($media))->response();
    }
}