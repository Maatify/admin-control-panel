<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-18 01:43
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use App\Domain\Telemetry\Contracts\TelemetryMetadataReaderInterface;
use App\Ui\Support\TelemetryMetadataViewNormalizer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Views\Twig;

final readonly class UiTelemetryMetadataController
{
    public function __construct(
        private Twig $view,
        private TelemetryMetadataReaderInterface $reader,
        private TelemetryMetadataViewNormalizer $normalizer
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function view(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $id = isset($args['id']) ? (int) $args['id'] : 0;

        if ($id <= 0) {
            throw new RuntimeException('Invalid telemetry id');
        }

        $telemetry = $this->reader->getById($id);
        $safeMetadata = $this->normalizer->normalize($telemetry->metadata);

        return $this->view->render(
            $response,
            'pages/telemetry_metadata.twig',
            [
                'telemetry' => $telemetry,
                'metadata'  => $safeMetadata,
            ]
        );
    }
}


