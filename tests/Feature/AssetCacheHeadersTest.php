<?php

namespace Tests\Feature;

use Tests\TestCase;

class AssetCacheHeadersTest extends TestCase
{
    public function test_public_html_is_not_cached_with_stale_asset_references(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0');
    }
}
