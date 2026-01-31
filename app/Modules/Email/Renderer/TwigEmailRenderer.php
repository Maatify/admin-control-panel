<?php

declare(strict_types=1);

namespace App\Modules\Email\Renderer;

use Maatify\AdminKernel\Domain\DTO\Email\EmailPayloadInterface;
use App\Modules\Email\DTO\RenderedEmailDTO;
use App\Modules\Email\Exception\EmailRenderException;
use Throwable;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigEmailRenderer implements EmailRendererInterface
{
    private Environment $twig;

    public function __construct(?string $templateDir = null)
    {
        // Calculate path to templates directory: root/templates
        // Current file: app/Modules/Email/Renderer/TwigEmailRenderer.php
        // Depth: 4 (app/Modules/Email/Renderer)
        $templateDir = $templateDir ?? (dirname(__DIR__, 4) . '/templates');

        $loader = new FilesystemLoader($templateDir);
        $this->twig = new Environment($loader, [
            'strict_variables' => true,
            'cache' => false, // Ensure no caching issues in this environment
        ]);
    }

    public function render(
        string $templateKey,
        string $language,
        EmailPayloadInterface $payload
    ): RenderedEmailDTO {
        // Enforce path: templates/emails/{templateKey}/{language}.twig
        // Loader is at templates/, so relative path is emails/...
        $templatePath = sprintf('emails/%s/%s.twig', $templateKey, $language);
        $data = $payload->toArray();

        try {
            // Load the template explicitly to extract blocks
            $template = $this->twig->load($templatePath);

            // Attempt to render the 'subject' block
            if (!$template->hasBlock('subject')) {
                throw new EmailRenderException("Template '{$templatePath}' is missing required block 'subject'.");
            }

            $subject = trim($template->renderBlock('subject', $data));
            if ($subject === '') {
                throw new EmailRenderException("Subject block in '{$templatePath}' rendered empty string.");
            }

            // Render the full body (which includes the layout via extends)
            $htmlBody = $template->render($data);

            return new RenderedEmailDTO(
                subject: $subject,
                htmlBody: $htmlBody,
                templateKey: $templateKey,
                language: $language
            );

        } catch (EmailRenderException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new EmailRenderException(
                "Failed to render email template '{$templateKey}' ({$language}): " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
