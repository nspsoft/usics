<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaviconTest extends TestCase
{
    use RefreshDatabase;

    public function test_favicon_endpoint_returns_image(): void
    {
        $response = $this->get('/favicon.png');

        $response->assertStatus(200);
        $response->assertHeader('content-type');
    }
}

