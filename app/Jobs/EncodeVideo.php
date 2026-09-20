<?php

namespace App\Jobs;

use App\Models\Video;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Throwable;

class EncodeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 3;

    public function __construct(public Video $video) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $video = $this->video->fresh();

        if (! $video || blank($video->path)) {
            Log::error('EncodeVideo: missing video or source path.', ['video_id' => $this->video->id]);

            return;
        }

        $disk = $video->disk ?: 'spaces';

        if (! Storage::disk($disk)->exists($video->path)) {
            $video->update(['status' => 'failed', 'error_message' => 'The uploaded source video could not be found.']);

            Log::error('EncodeVideo: source file not found.', [
                'video_id' => $video->id,
                'disk' => $disk,
                'path' => $video->path,
            ]);

            return;
        }

        $baseName = pathinfo($video->path, PATHINFO_FILENAME);
        $version = Str::lower((string) Str::ulid());
        $outputFolder = "videos/hls/{$baseName}-{$version}";
        $masterPlaylist = "{$outputFolder}/master.m3u8";
        $previousEncodedPath = $video->encoded_path;

        $video->update(['status' => 'processing', 'error_message' => null]);

        try {
            $hls = FFMpeg::fromDisk($disk)
                ->open($video->path)
                ->exportForHLS()
                ->setSegmentLength(10);

            $renditions = [
                ['bitrate' => 6000, 'width' => 1920, 'height' => 1080],
                ['bitrate' => 3200, 'width' => 1280, 'height' => 720],
                ['bitrate' => 1200, 'width' => 854, 'height' => 480],
            ];

            foreach ($renditions as $rendition) {
                $format = (new X264)
                    ->setKiloBitrate($rendition['bitrate'])
                    ->setAudioCodec('aac')
                    ->setAdditionalParameters(['-preset', 'veryfast']);

                $hls->addFormat($format, function ($media) use ($rendition): void {
                    $media->resize($rendition['width'], $rendition['height']);
                });
            }

            $hls->toDisk($disk)->save($masterPlaylist);

            $video->update([
                'encoded' => true,
                'encoded_path' => $masterPlaylist,
                'status' => 'ready',
                'error_message' => null,
            ]);

            if (filled($previousEncodedPath)) {
                $previousOutputFolder = dirname($previousEncodedPath);

                if (str_starts_with($previousOutputFolder, 'videos/hls/') && $previousOutputFolder !== $outputFolder) {
                    try {
                        Storage::disk($disk)->deleteDirectory($previousOutputFolder);
                    } catch (Throwable $cleanupException) {
                        Log::warning('EncodeVideo: old adaptive stream could not be removed.', [
                            'video_id' => $video->id,
                            'path' => $previousOutputFolder,
                            'exception' => $cleanupException->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('EncodeVideo: adaptive encoding complete.', [
                'video_id' => $video->id,
                'playlist' => $masterPlaylist,
            ]);
        } catch (Throwable $exception) {
            $errorMessage = $exception->getMessage();
            $errorTail = mb_substr($errorMessage, -6000);

            $video->update([
                'encoded' => false,
                'status' => 'failed',
                'error_message' => "FFmpeg encoding failed. Relevant error output:\n{$errorTail}",
            ]);

            Log::error('EncodeVideo: encoding failed.', [
                'video_id' => $video->id,
                'exception' => $errorMessage,
            ]);

            throw $exception;
        }
    }
}
