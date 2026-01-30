<?php

declare(strict_types=1);

namespace Tests\Integration\Kernel;

use App\Kernel\AdminKernel;
use App\Kernel\KernelOptions;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\Support\TestKernelFactory;

class KernelRoutesInjectionTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        $this->fixturePath = __DIR__ . '/../../Fixtures/custom_routes.php';
        if (!file_exists(dirname($this->fixturePath))) {
            mkdir(dirname($this->fixturePath), 0777, true);
        }

        $content = <<<'PHP'
<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    $app->get('/custom-kernel-test', function (Request $request, Response $response) {
        $response->getBody()->write('Hello from custom routes!');
        return $response;
    });
};
PHP;
        file_put_contents($this->fixturePath, $content);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->fixturePath)) {
            unlink($this->fixturePath);
        }
    }

    public function testBootWithCustomRoutesFile(): void
    {
        // Setup options
        $runtimeConfig = TestKernelFactory::createRuntimeConfig();

        $options = new KernelOptions();
        $options->runtimeConfig = $runtimeConfig;
        $options->routesFilePath = $this->fixturePath;

        $app = AdminKernel::bootWithOptions($options);

        // Create Request
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/custom-kernel-test');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello from custom routes!', (string)$response->getBody());

        // Ensure default routes are NOT loaded (e.g. /health)
        // Since we replaced the routes file, and the custom file doesn't register default routes.
        $requestHealth = (new ServerRequestFactory())->createServerRequest('GET', '/health');
        $responseHealth = $app->handle($requestHealth);
        $this->assertEquals(404, $responseHealth->getStatusCode());
    }

    public function testBootWithDefaultRoutes(): void
    {
         // Setup options
        $runtimeConfig = TestKernelFactory::createRuntimeConfig();

        $options = new KernelOptions();
        $options->runtimeConfig = $runtimeConfig;
        // routesFilePath is null by default

        $app = AdminKernel::bootWithOptions($options);

        // Instead of executing the request (which requires DB for middleware),
        // we verify that the default routes are registered in the RouteCollector.
        $routeCollector = $app->getRouteCollector();
        $routes = $routeCollector->getRoutes();

        $healthRouteFound = false;
        foreach ($routes as $route) {
            if ($route->getPattern() === '/health') {
                $healthRouteFound = true;
                break;
            }
        }

        $this->assertTrue($healthRouteFound, 'Route /health should be registered by default routes');
    }
}
