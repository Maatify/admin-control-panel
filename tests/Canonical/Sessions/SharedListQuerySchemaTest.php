<?php

declare(strict_types=1);

namespace Tests\Canonical\Sessions;

use App\Modules\Validation\Schemas\SharedListQuerySchema;
use PHPUnit\Framework\TestCase;

class SharedListQuerySchemaTest extends TestCase
{
    private SharedListQuerySchema $schema;

    protected function setUp(): void
    {
        $this->schema = new SharedListQuerySchema();
    }

    public function test_accepts_valid_minimal_payload(): void
    {
        $payload = [
            'page' => 1,
            'per_page' => 20,
        ];
        $result = $this->schema->validate($payload);
        $this->assertTrue($result->isValid(), 'Minimal payload should be valid');
    }

    public function test_rejects_forbidden_keys(): void
    {
        $forbiddenKeys = ['filters', 'limit', 'items', 'meta', 'from_date', 'to_date', 'unknown_key'];

        foreach ($forbiddenKeys as $key) {
            $payload = [
                'page' => 1,
                $key => 'some_value',
            ];
            $result = $this->schema->validate($payload);
            $this->assertFalse($result->isValid(), "Schema MUST reject forbidden key: $key");
        }
    }

    public function test_rejects_empty_search_block(): void
    {
        // "Reject empty search blocks"
        // Interpreted as search: {} or search: [] provided but empty.
        // The rule SearchQueryRule might enforce something, or the structure requirement.
        // If "search" is optional, it should be omitted if empty.
        // If provided, it likely must have structure.

        $payload = [
            'page' => 1,
            'search' => [],
        ];
        $result = $this->schema->validate($payload);
        // This expectation depends on strict interpretation.
        // Docs: "MUST be omitted if empty".
        // If the schema allows empty array, this test might fail the assertion of rejection.
        // But strict contract says "Reject".
        $this->assertFalse($result->isValid(), 'Should reject empty search block');
    }

    public function test_rejects_partial_date_block(): void
    {
        // "Reject partial date blocks" - DateRangeRule likely enforces both from/to or neither.
        $payloads = [
            ['date' => ['from' => '2023-01-01']],
            ['date' => ['to' => '2023-01-01']],
        ];

        foreach ($payloads as $payload) {
            $payload['page'] = 1;
            $result = $this->schema->validate($payload);
            $this->assertFalse($result->isValid(), 'Should reject partial date block');
        }
    }

    public function test_accepts_valid_full_payload(): void
    {
        $payload = [
            'page' => 1,
            'per_page' => 50,
            'search' => [
                'global' => 'something',
                'columns' => ['status' => 'active'],
            ],
            'date' => [
                'from' => '2023-01-01',
                'to' => '2023-01-31',
            ],
        ];
        $result = $this->schema->validate($payload);
        $this->assertTrue($result->isValid(), 'Full valid payload should be accepted');
    }
}
