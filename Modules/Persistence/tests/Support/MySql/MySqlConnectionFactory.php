<?php

declare(strict_types=1);

namespace Maatify\Persistence\Tests\Support\MySql;

use PDO;
use PDOException;
use PHPUnit\Framework\AssertionFailedError;

final class MySqlConnectionFactory
{
    private const DSN_ENV = 'PERSISTENCE_TEST_MYSQL_DSN';
    private const USER_ENV = 'PERSISTENCE_TEST_MYSQL_USER';
    private const PASSWORD_ENV = 'PERSISTENCE_TEST_MYSQL_PASSWORD';

    public function create(): PDO
    {
        $dsn = $this->requiredEnv(self::DSN_ENV);
        $user = $this->requiredEnv(self::USER_ENV);
        $password = $this->optionalEnv(self::PASSWORD_ENV);

        try {
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException) {
            throw new AssertionFailedError(
                'Unable to connect to configured MySQL test database.'
            );
        }

        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new AssertionFailedError('Configured integration database must use the MySQL PDO driver.');
        }

        return $pdo;
    }

    /**
     * @param non-empty-string $name
     *
     * @return non-empty-string
     */
    private function requiredEnv(string $name): string
    {
        $value = getenv($name);

        if (!is_string($value) || $value === '') {
            throw new AssertionFailedError(sprintf('Required MySQL integration environment variable %s is missing or empty.', $name));
        }

        return $value;
    }

    /**
     * @param non-empty-string $name
     */
    private function optionalEnv(string $name): string
    {
        $value = getenv($name);

        return is_string($value) ? $value : '';
    }
}
