<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-12 23:10
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Infrastructure\Mysql;

use Maatify\I18n\Contract\I18nTransactionManagerInterface;
use PDO;

final readonly class MysqlI18nTransactionManager implements I18nTransactionManagerInterface
{
    public function __construct(private PDO $connection) {}

    public function run(callable $callback): mixed
    {
        if ($this->connection->inTransaction()) {
            return $callback(); // nested call safe
        }

        $this->connection->beginTransaction();

        try {
            $result = $callback();
            $this->connection->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
