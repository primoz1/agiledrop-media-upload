<?php namespace App\Http\Controllers;

use App\Jobs\ProcessMediaUpload;
use App\Models\Media;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
                                            'title'       => ['required', 'string', 'max:255'],
                                            'description' => ['nullable', 'string'],
                                            'file'        => ['required',
                                                              'file',
                                                              // basic guard: accept common image/video
                                                              'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/x-matroska,video/webm'
                                            ],
                                        ]);

        $user   = $request->user(); // Sanctum resolves this from Bearer token

        if (!$user) {
            return response()->json(['message' => 'User has no client assigned.'], 403);
        }

        // first save to the database, so we can use the ID for file storage
        $media = Media::create([
                                   'title'       => $validated['title'],
                                   'description' => $validated['description'] ?? NULL,
                                   'uploaded_by' => $user->id,
                                   'status'      => 'queued',
                               ]);

        // store original quickly (no heavy processing)
        $path = $request->file('file')->store("media/original/{$media->id}", 'public');

        $media->update([
                           'original_path' => $path,
                           'status'        => 'processing',
                       ]);

        ProcessMediaUpload::dispatch($media->id);

        return response()->json([
                                    'id'         => $media->id,
                                    'status'     => $media->status,
                                    'status_url' => url("/api/media/{$media->id}/status"),
                                ],
                                202);
    }

    public function status(Media $media)
    {
        $user = \request()->user();

        if ($media->client_id !== $user->uploaded_by) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json([
                                    'id'             => $media->id,
                                    'status'         => $media->status,
                                    'type'           => $media->type,
                                    'original_path'  => $media->original_path,
                                    'thumbnail_path' => $media->thumbnail_path,
                                    'error_message'  => $media->error_message,
                                ]);
    }
}