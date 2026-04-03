<?php

declare(strict_types=1);

namespace Maatify\Currency\Bootstrap;

use Maatify\Currency\Contract\CurrencyCommandRepositoryInterface;
use Maatify\Currency\Contract\CurrencyQueryReaderInterface;
use Maatify\Currency\Exception\CurrencyPersistenceException;
use Maatify\Currency\Infrastructure\Repository\PdoCurrencyCommandRepository;
use Maatify\Currency\Infrastructure\Repository\PdoCurrencyQueryReader;
use Maatify\Currency\Service\CurrencyCommandService;
use Maatify\Currency\Service\CurrencyQueryService;
use PDO;
use Psr\Container\ContainerInterface;

/**
 * PHP-DI definitions for the Currencies module.
 *
 * Usage in your Slim 4 bootstrap:
 *
 *   $builder = new \DI\ContainerBuilder();
 *   $builder->addDefinitions(CurrenciesBindings::definitions());
 *   $container = $builder->build();
 *
 * Prerequisites:
 *   • A PDO::class entry registered in the container.
 *   • PDO configured with PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
 *     so all DB errors surface as PDOException rather than silent failures.
 */
final class CurrenciesBindings
{
    /**
     * @return array<string, callable(ContainerInterface): object>
     */
    public static function definitions(): array
    {
        return [

            // --- Infrastructure -----------------------------------------

            CurrencyQueryReaderInterface::class =>
                static function (ContainerInterface $c): PdoCurrencyQueryReader {
                    return new PdoCurrencyQueryReader(self::getPdo($c));
                },

            CurrencyCommandRepositoryInterface::class =>
                static function (ContainerInterface $c): PdoCurrencyCommandRepository {
                    $queryReader = $c->get(CurrencyQueryReaderInterface::class);
                    if (!$queryReader instanceof CurrencyQueryReaderInterface) {
                        throw CurrencyPersistenceException::containerTypeMismatch(
                            CurrencyQueryReaderInterface::class,
                        );
                    }
                    return new PdoCurrencyCommandRepository(self::getPdo($c), $queryReader);
                },

            // --- Services -----------------------------------------------

            CurrencyQueryService::class =>
                static function (ContainerInterface $c): CurrencyQueryService {
                    $reader = $c->get(CurrencyQueryReaderInterface::class);
                    if (!$reader instanceof CurrencyQueryReaderInterface) {
                        throw CurrencyPersistenceException::containerTypeMismatch(
                            CurrencyQueryReaderInterface::class,
                        );
                    }

                    return new CurrencyQueryService($reader);
                },

            CurrencyCommandService::class =>
                static function (ContainerInterface $c): CurrencyCommandService {
                    $commandRepo = $c->get(CurrencyCommandRepositoryInterface::class);
                    if (!$commandRepo instanceof CurrencyCommandRepositoryInterface) {
                        throw CurrencyPersistenceException::containerTypeMismatch(
                            CurrencyCommandRepositoryInterface::class,
                        );
                    }

                    $queryReader = $c->get(CurrencyQueryReaderInterface::class);
                    if (!$queryReader instanceof CurrencyQueryReaderInterface) {
                        throw CurrencyPersistenceException::containerTypeMismatch(
                            CurrencyQueryReaderInterface::class,
                        );
                    }

                    return new CurrencyCommandService($commandRepo, $queryReader);
                },

        ];
    }

    // ------------------------------------------------------------------ //
    //  Private helper
    // ------------------------------------------------------------------ //

    private static function getPdo(ContainerInterface $c): PDO
    {
        $pdo = $c->get(PDO::class);
        if (!$pdo instanceof PDO) {
            throw CurrencyPersistenceException::containerTypeMismatch(PDO::class);
        }

        return $pdo;
    }
}
