<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class PingTest extends TestCase
{
    public function test_ping_returns_pong(): void
    {
        $response = $this->getJson('/api/v1/ping');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'pong')
            ->assertJsonStructure(['message', 'time']);
    }
}
