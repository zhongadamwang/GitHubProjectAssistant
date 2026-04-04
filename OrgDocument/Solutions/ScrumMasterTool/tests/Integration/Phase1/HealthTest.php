<?php
declare(strict_types=1);

namespace Tests\Integration\Phase1;

use Tests\Integration\IntegrationTestCase;

/**
 * HealthTest — verifies the GET /api/health smoke-test endpoint (T004).
 */
final class HealthTest extends IntegrationTestCase
{
    public function test_health_returns_200(): void
    {
        $response = $this->request('GET', '/api/health');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_health_returns_json_status_ok(): void
    {
        $response = $this->request('GET', '/api/health');
        $body     = $this->json($response);

        $this->assertSame(['status' => 'ok'], $body);
    }

    public function test_health_response_has_json_content_type(): void
    {
        $response = $this->request('GET', '/api/health');

        $this->assertStringContainsString(
            'application/json',
            $response->getHeaderLine('Content-Type'),
        );
    }
}
