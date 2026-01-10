<?php

declare(strict_types=1);

namespace App\Modules\Email\Renderer;

use App\Domain\DTO\Email\EmailPayloadInterface;
use App\Modules\Email\DTO\RenderedEmailDTO;
use App\Modules\Email\Exception\EmailRenderingException;
use Slim\Views\Twig;
use Throwable;

class TwigEmailRenderer implements EmailRendererInterface
{
    public function __construct(
        private Twig $twig
    ) {
    }

    public function render(
        string $templateKey,
        string $language,
        EmailPayloadInterface $payload
    ): RenderedEmailDTO {
        $templatePath = sprintf('emails/%s/%s.twig', $templateKey, $language);
        $data = $payload->toArray();

        try {
            // Load the template explicitly to extract blocks
            $template = $this->twig->getEnvironment()->load($templatePath);

            // Attempt to render the 'subject' block
            if (!$template->hasBlock('subject')) {
                throw new EmailRenderingException("Template '{$templatePath}' is missing required block 'subject'.");
            }

            $subject = trim($template->renderBlock('subject', $data));
            if ($subject === '') {
                throw new EmailRenderingException("Subject block in '{$templatePath}' rendered empty string.");
            }

            // Render the full body (which includes the layout via extends)
            $htmlBody = $template->render($data);

            return new RenderedEmailDTO(
                subject: $subject,
                htmlBody: $htmlBody,
                templateKey: $templateKey,
                language: $language
            );

        } catch (EmailRenderingException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new EmailRenderingException(
                "Failed to render email template '{$templateKey}' ({$language}): " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
