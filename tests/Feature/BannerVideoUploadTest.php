<?php

namespace Tests\Feature;

use App\Jobs\EncodeVideo;
use App\Models\AdminModuleRecord;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerVideoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_banner_with_a_video_and_queue_encoding(): void
    {
        Storage::fake('spaces');
        Queue::fake();
        config()->set('video.disk', 'spaces');

        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.modules.store', 'banners'), [
            'title' => 'Homepage video',
            'status' => 'active',
            'summary' => 'Homepage hero',
            'video' => UploadedFile::fake()->create('homepage.mp4', 250, 'video/mp4'),
        ]);

        $banner = AdminModuleRecord::query()
            ->where('module_slug', 'banners')
            ->with('video')
            ->firstOrFail();

        $response->assertRedirect(route('admin.modules.record.show', ['banners', $banner->id]));
        $this->assertNotNull($banner->video);
        $this->assertSame('uploaded', $banner->video->status);
        $this->assertSame('homepage.mp4', $banner->video->filename);
        Storage::disk('spaces')->assertExists($banner->video->path);
        Queue::assertPushed(EncodeVideo::class, fn (EncodeVideo $job) => $job->video->is($banner->video));
    }

    public function test_uploading_a_replacement_video_keeps_one_reference_and_removes_the_old_source(): void
    {
        Storage::fake('spaces');
        Queue::fake();
        config()->set('video.disk', 'spaces');

        $admin = User::factory()->create(['is_admin' => true]);
        $banner = AdminModuleRecord::create([
            'module_slug' => 'banners',
            'title' => 'Homepage video',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->put(route('admin.modules.record.update', ['banners', $banner->id]), [
            'title' => 'Homepage video',
            'status' => 'active',
            'video' => UploadedFile::fake()->create('first.mp4', 250, 'video/mp4'),
        ])->assertRedirect();

        $oldPath = $banner->fresh()->video->path;
        Storage::disk('spaces')->assertExists($oldPath);

        $this->actingAs($admin)->put(route('admin.modules.record.update', ['banners', $banner->id]), [
            'title' => 'Homepage video',
            'status' => 'active',
            'video' => UploadedFile::fake()->create('second.mp4', 250, 'video/mp4'),
        ])->assertRedirect();

        $video = $banner->fresh()->video;

        $this->assertSame(1, Video::query()
            ->where('videoable_type', AdminModuleRecord::class)
            ->where('videoable_id', $banner->id)
            ->count());
        $this->assertSame('second.mp4', $video->filename);
        Storage::disk('spaces')->assertMissing($oldPath);
        Storage::disk('spaces')->assertExists($video->path);
    }
}
