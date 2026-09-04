<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Error;

use Maatify\AbuseProtection\Exception\ChallengeRequiredException;
use Maatify\AdminKernel\Domain\Contracts\Abuse\ChallengeWidgetRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Throwable;

final readonly class ChallengeRequiredErrorHandler
{
    public function __construct(
        private Twig $view,
        private ChallengeWidgetRendererInterface $challengeRenderer
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails
    ): ResponseInterface {
        /** @var ResponseInterface $response */
        $response = new \Slim\Psr7\Response();

        $challengeWidget = null;
        if ($this->challengeRenderer->isEnabled()) {
            $challengeWidget = $this->challengeRenderer->renderWidgetHtml();
        }

        return $this->view->render(
            $response,
            'login.twig',
            [
                'error'             => 'Please complete the security challenge.',
                'require_challenge' => true,
                'challenge_widget'  => $challengeWidget,
                'challenge_error'   => $exception->getMessage(),
            ]
        );
    }
}
