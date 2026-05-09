<?php

namespace App\Modules\PhotoRepository\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PhotoRepository\Models\MediaDownloadLog;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoDownloadController extends Controller
{
    public function __invoke(Request $request, MediaPhoto $mediaPhoto): StreamedResponse
    {
        $mediaPhoto->loadMissing(['profile', 'category']);

        Gate::authorize('download', $mediaPhoto);

        abort_unless(Storage::disk('public')->exists($mediaPhoto->photo_path), 404);

        $mediaPhoto->increment('download_count');

        MediaDownloadLog::create([
            'media_photo_id' => $mediaPhoto->id,
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'downloaded_at' => now(),
        ]);

        return Storage::disk('public')->download($mediaPhoto->photo_path, $mediaPhoto->downloadFilename());
    }
}
