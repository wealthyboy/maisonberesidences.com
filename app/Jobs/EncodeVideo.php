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
        $outputFolder = "videos/hls/{$baseName}";
        $masterPlaylist = "{$outputFolder}/master.m3u8";

        $video->update(['status' => 'processing', 'error_message' => null]);

        try {
            $hls = FFMpeg::fromDisk($disk)
                ->open($video->path)
                ->exportForHLS()
                ->setSegmentLength(10);

            foreach ([2000, 800] as $bitrate) {
                $format = (new X264)
                    ->setKiloBitrate($bitrate)
                    ->setAudioCodec('aac')
                    ->setAdditionalParameters(['-preset', 'veryfast', '-movflags', '+faststart']);

                $hls->addFormat($format);
            }

            $hls->toDisk($disk)->save($masterPlaylist);

            $video->update([
                'encoded' => true,
                'encoded_path' => $masterPlaylist,
                'status' => 'ready',
                'error_message' => null,
            ]);

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
