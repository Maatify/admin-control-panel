# API Integration Guide

This guide explains how to integrate `maatify/exceptions` with your API layer.

## 1. Global Exception Handler

Every modern PHP application should have a global exception handler.

### Middleware Approach

If you are using PSR-15 Middleware:

```php
public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
{
    try {
        return $handler->handle($request);
    } catch (ApiAwareExceptionInterface $e) {
        return $this->responseFactory->createResponse($e->getHttpStatus())
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->formatJson($e));
    } catch (Throwable $e) {
        // Fallback for non-Maatify exceptions
        return $this->responseFactory->createResponse(500)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(json_encode(['error' => 'Internal Server Error']));
    }
}
```

## 2. JSON Format

We recommend a consistent JSON structure for error responses:

```json
{
    "error": {
        "category": "VALIDATION",
        "code": "INVALID_ARGUMENT",
        "message": "Email field is required",
        "meta": {
            "field": "email"
        }
    }
}
```

### Generating the Response Body

```php
private function formatJson(ApiAwareExceptionInterface $e): string
{
    $payload = [
        'error' => [
            'category' => $e->getCategory()->value,
            'code' => $e->getErrorCode()->value,
            'message' => $e->getMessage(), // Only if isSafe() is true!
        ]
    ];

    if (!$e->isSafe()) {
        $payload['error']['message'] = 'An internal error occurred.';
    }

    if (!empty($e->getMeta())) {
        $payload['error']['meta'] = $e->getMeta();
    }

    return json_encode($payload);
}
```

## 3. Logging

Always log the full exception stack trace, especially for System errors.

```php
$logger->error($e->getMessage(), [
    'exception' => $e,
    'category' => $e->getCategory()->value,
    'code' => $e->getErrorCode()->value
]);
```
