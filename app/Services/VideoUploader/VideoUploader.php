<?php

namespace App\Services\VideoUploader;

use App\Jobs\EncodeVideo;
use App\Models\Video;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoUploader
{
    public static function uploadAndEncode(
        UploadedFile $file,
        Video $video,
        string $disk = 'spaces',
        string $directory = 'videos/sources',
    ): Video {
        $oldDisk = $video->exists ? ($video->disk ?: $disk) : null;
        $oldPath = $video->exists ? $video->path : null;
        $oldEncodedPath = $video->exists ? $video->encoded_path : null;

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'mp4');
        $filename = Str::uuid().'.'.$extension;
        $path = $file->storeAs(trim($directory, '/'), $filename, $disk);

        if (! $path) {
            throw new \RuntimeException('The video could not be uploaded to storage.');
        }

        $video->fill([
            'filename' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'encoded' => false,
            'encoded_path' => null,
            'status' => 'uploaded',
            'error_message' => null,
        ])->save();

        if ($oldDisk && $oldPath && $oldPath !== $path) {
            Storage::disk($oldDisk)->delete($oldPath);
        }

        if ($oldDisk && $oldEncodedPath) {
            Storage::disk($oldDisk)->deleteDirectory(dirname($oldEncodedPath));
        }

        EncodeVideo::dispatch($video)->afterCommit();

        return $video;
    }
}
