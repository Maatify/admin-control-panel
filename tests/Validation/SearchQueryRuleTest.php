<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-12 12:18
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Validation;

use Maatify\Validation\Rules\SearchQueryRule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SearchQueryRuleTest extends TestCase
{
    #[Test]
    public function it_accepts_numeric_column_search_value(): void
    {
        $input = [
            'columns' => [
                'admin_id' => '1',
            ],
        ];

        $this->assertTrue(
            SearchQueryRule::rule()->validate($input),
            'Numeric string column values should be accepted'
        );
    }

    #[Test]
    public function it_accepts_string_column_search_value(): void
    {
        $input = [
            'columns' => [
                'session_id' => 'f48d0f310353292229ee4b377f4043e4ccc1926ce28f8a3bd5eb107e259abe98',
            ],
        ];

        $this->assertTrue(
            SearchQueryRule::rule()->validate($input),
            'String column values should be accepted'
        );
    }

    #[Test]
    public function it_rejects_empty_columns(): void
    {
        $input = [
            'columns' => [],
        ];

        $this->assertFalse(
            SearchQueryRule::rule()->validate($input),
            'Empty columns should be rejected'
        );
    }
}
