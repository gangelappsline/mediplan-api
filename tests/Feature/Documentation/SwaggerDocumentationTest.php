<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete([
            storage_path('api-docs/api-docs.json'),
            storage_path('api-docs/api-docs.yaml'),
        ]);

        parent::tearDown();
    }

    public function test_openapi_documentation_describes_every_api_endpoint(): void
    {
        $this->artisan('l5-swagger:generate')->assertExitCode(0);

        $specificationPath = storage_path('api-docs/api-docs.json');

        $this->assertFileExists($specificationPath);

        /** @var array{openapi: string, paths: array<string, mixed>} $specification */
        $specification = json_decode(File::get($specificationPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('3.0.0', $specification['openapi']);
        $this->assertEqualsCanonicalizing(
            ['/register', '/login', '/logout', '/me'],
            array_keys($specification['paths']),
        );
        $this->assertArrayHasKey('bearerAuth', $specification['components']['securitySchemes']);
    }

    public function test_documentation_routes_are_publicly_readable(): void
    {
        $this->artisan('l5-swagger:generate')->assertExitCode(0);

        $this->get('/api/documentation')->assertOk();
        $this->get('/docs')->assertOk()->assertJsonPath('openapi', '3.0.0');
    }
}
