<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use App\Application\Telemetry\HttpTelemetryAdminRecorder;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Telemetry\Recorder\TelemetryRecorderInterface;
use App\Http\Controllers\Web\DashboardController;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Telemetry\Enum\TelemetrySeverityEnum;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class UiDashboardController
{
    public function __construct(
        private DashboardController $webDashboard,
        private TelemetryRecorderInterface $telemetry
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $context = $request->getAttribute(RequestContext::class);
        if ($context instanceof RequestContext) {
            $recorder = new HttpTelemetryAdminRecorder($this->telemetry, $context);
            $adminContext = $request->getAttribute(AdminContext::class);
            $adminId = $adminContext instanceof AdminContext ? $adminContext->adminId : null;

            $recorder->record(
                $adminId,
                TelemetryEventTypeEnum::HTTP_REQUEST_END,
                TelemetrySeverityEnum::INFO,
                ['controller' => 'UiDashboardController']
            );
        }

        return $this->webDashboard->index(
            $request->withAttribute('template', 'pages/dashboard.twig'),
            $response
        );
    }
}
