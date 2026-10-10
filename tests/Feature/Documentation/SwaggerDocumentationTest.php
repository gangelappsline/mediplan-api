<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    /**
     * Contenido previo de la especificación, para no borrar la documentación
     * que el desarrollador ya tenía generada en local.
     *
     * @var array<string, string|null>
     */
    private array $previousSpecification = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->specificationPaths() as $path) {
            $this->previousSpecification[$path] = File::exists($path) ? File::get($path) : null;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->previousSpecification as $path => $contents) {
            if ($contents === null) {
                File::delete($path);

                continue;
            }

            File::ensureDirectoryExists(dirname($path));
            File::put($path, $contents);
        }

        parent::tearDown();
    }

    /**
     * @return list<string>
     */
    private function specificationPaths(): array
    {
        return [
            storage_path('api-docs/api-docs.json'),
            storage_path('api-docs/api-docs.yaml'),
        ];
    }

    public function test_openapi_documentation_describes_every_api_endpoint(): void
    {
        $this->artisan('l5-swagger:generate')->assertExitCode(0);

        $specificationPath = storage_path('api-docs/api-docs.json');

        $this->assertFileExists($specificationPath);

        /** @var array{openapi: string, paths: array<string, mixed>, tags?: array<int, array{name: string}>} $specification */
        $specification = json_decode(File::get($specificationPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('3.0.0', $specification['openapi']);
        $this->assertEqualsCanonicalizing(
            $this->applicationApiPaths(),
            array_keys($specification['paths']),
        );
        $this->assertArrayHasKey('bearerAuth', $specification['components']['securitySchemes']);

        // Los endpoints nuevos deben quedar agrupados por módulo.
        $tags = array_column($specification['tags'] ?? [], 'name');

        foreach (['Autenticación', 'Panel del negocio', 'Agenda del negocio', 'Panel de administración'] as $tag) {
            $this->assertContains($tag, $tags);
        }
    }

    public function test_documentation_routes_are_publicly_readable(): void
    {
        $this->artisan('l5-swagger:generate')->assertExitCode(0);

        $this->get('/api/documentation')->assertOk();
        $this->get('/docs')->assertOk()->assertJsonPath('openapi', '3.0.0');
    }

    /**
     * Rutas de la aplicación (sin las de la documentación) en formato OpenAPI.
     *
     * @return array<int, string>
     */
    private function applicationApiPaths(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (\Illuminate\Routing\Route $route) => str_starts_with(
                (string) $route->getAction('controller'),
                'App\\Http\\Controllers\\',
            ))
            ->map(fn (\Illuminate\Routing\Route $route) => '/'.ltrim(Str::after($route->uri(), 'api/'), '/'))
            ->unique()
            ->values()
            ->all();
    }
}
