<?php

declare(strict_types=1);

namespace Tests\Unit\Kernel;

use App\Kernel\AdminKernel;
use App\Kernel\KernelOptions;
use PHPUnit\Framework\TestCase;
use Slim\App;

class AdminKernelTest extends TestCase
{
    public function test_bootWithOptions_uses_custom_bootstrap(): void
    {
        $bootstrapped = false;

        $options = new KernelOptions(
            rootPath: __DIR__ . '/../../../', // Repo root
            loadEnv: false, // Don't load .env, rely on bootstrap.php defaults
            bootstrap: function (App $app) use (&$bootstrapped) {
                $bootstrapped = true;
            }
        );

        $app = AdminKernel::bootWithOptions($options);

        $this->assertInstanceOf(App::class, $app);
        $this->assertTrue($bootstrapped, 'Custom bootstrap was not executed');
    }

    public function test_kernel_options_structure(): void
    {
        $hook = function() {};
        $bootstrap = function() {};

        $options = new KernelOptions(
            rootPath: '/tmp',
            loadEnv: true,
            builderHook: $hook,
            bootstrap: $bootstrap
        );

        $this->assertSame('/tmp', $options->rootPath);
        $this->assertTrue($options->loadEnv);
        $this->assertSame($hook, $options->builderHook);
        $this->assertSame($bootstrap, $options->bootstrap);
    }
}
