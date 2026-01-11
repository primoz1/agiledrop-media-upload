<?php namespace App\Http\Controllers;

use App\Enum\MediaStatus;
use App\Http\Requests\GetMediaStatusRequest;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Jobs\ProcessMediaUpload;
use App\Models\Media;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    /**
     * Store a new media upload and dispatch a processing job.
     */
    public function store(StoreMediaRequest $request): MediaResource
    {
        return DB::transaction(function () use ($request) {
            $user = $request->user();

            // Initialize media record using relationship for automatic user association
            $media = $user->media()->create([
                                                'title'       => $request->validated('title'),
                                                'description' => $request->validated('description'),
                                                'status'      => MediaStatus::Queued,
                                                'client_id'   => $user->client_id, // Associate with user's client
                                            ]);

            // Quickly store the original file to disk using media ID as folder
            $path = $request->file('file')
                            ->store("media/original/{$media->id}", 'public');

            // Update path and move to processing state
            $media->update([
                               'original_path' => $path,
                               'status'        => MediaStatus::Queued,
                           ]);

            ProcessMediaUpload::dispatch($media->id);

            return (new MediaResource($media))
                ->response()
                ->setStatusCode(202);
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