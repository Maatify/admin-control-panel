<?php

declare(strict_types=1);

namespace App\Context;

use App\Context\Resolver\AdminContextResolver;
use App\Context\Resolver\RequestContextResolver;
use Psr\Http\Message\ServerRequestInterface;

final class HttpContextProvider implements ContextProviderInterface
{
    private ?AdminContext $adminContext = null;
    private ?RequestContext $requestContext = null;

    private bool $adminContextResolved = false;
    private bool $requestContextResolved = false;

    public function __construct(
        private ServerRequestInterface $request,
        private AdminContextResolver $adminResolver,
        private RequestContextResolver $requestResolver,
    ) {
    }

    public function admin(): ?AdminContext
    {
        if ($this->adminContextResolved) {
            return $this->adminContext;
        }

        try {
            // We assume admin_id attribute is populated by middleware
            // But strict rule says "Context resolution happens in ONE place"
            // The Resolver reads attributes.
            // However, the resolver might throw if admin_id is missing.
            // Interface says: returns null if not authenticated.

            // Check if resolver throws or we check attribute first?
            // Existing AdminContextResolver throws "RuntimeException called without admin_id"
            // So we should check if attribute exists or catch the exception.
            // Checking attribute is safer/faster.

            $adminId = $this->request->getAttribute('admin_id');
            if (is_int($adminId)) {
                // Ensure resolver errors are caught
                try {
                    $this->adminContext = $this->adminResolver->resolve($this->request);
                } catch (\Throwable) {
                    $this->adminContext = null;
                }
            } else {
                $this->adminContext = null;
            }
        } catch (\Throwable $e) {
            $this->adminContext = null;
        }

        $this->adminContextResolved = true;
        return $this->adminContext;
    }

    public function request(): RequestContext
    {
        if ($this->requestContextResolved) {
            return $this->requestContext; // @phpstan-ignore-line (will be checked by return type)
        }

        // Must always return valid context
        $this->requestContext = $this->requestResolver->resolve($this->request);
        $this->requestContextResolved = true;

        return $this->requestContext;
    }
}
