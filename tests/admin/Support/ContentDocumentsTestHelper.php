<?php

declare(strict_types=1);

namespace Tests\Support;

final class ContentDocumentsTestHelper
{
    public static function reset(): void
    {
        // Leaf → Root
        $tables = [
            'document_acceptance',
            'document_translations',
            'documents',
            'document_types',
        ];

        foreach ($tables as $table) {
            try {
                MySQLTestHelper::truncate($table);
            } catch (\Throwable $e) {
                // Ignore safely if table not present in certain scopes
            }
        }
    }
}
