<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Maatify\ExchangeRates\Admin\Provider\Contract\ProviderCommandRepositoryInterface;
use Maatify\ExchangeRates\Admin\Provider\Contract\ProviderQueryRepositoryInterface;
use Maatify\ExchangeRates\Admin\Provider\Infrastructure\Repository\PdoProviderCommandRepository;
use Maatify\ExchangeRates\Admin\Provider\Infrastructure\Repository\PdoProviderQueryRepository;
use Maatify\ExchangeRates\Admin\Provider\Service\ProviderCommandService;
use Maatify\ExchangeRates\Admin\Provider\Service\ProviderQueryService;
use Maatify\ExchangeRates\Admin\Rate\Contract\RateCommandRepositoryInterface;
use Maatify\ExchangeRates\Admin\Rate\Contract\RateQueryRepositoryInterface;
use Maatify\ExchangeRates\Admin\Rate\Infrastructure\Repository\PdoRateCommandRepository;
use Maatify\ExchangeRates\Admin\Rate\Infrastructure\Repository\PdoRateQueryRepository;
use Maatify\ExchangeRates\Admin\Rate\Service\RateCommandService;
use Maatify\ExchangeRates\Admin\Rate\Service\RateQueryService;
use Maatify\ExchangeRates\Admin\RateHistory\Contract\RateHistoryQueryRepositoryInterface;
use Maatify\ExchangeRates\Admin\RateHistory\Infrastructure\Repository\PdoRateHistoryQueryRepository;
use Maatify\ExchangeRates\Admin\RateHistory\Service\RateHistoryQueryService;
use Maatify\ExchangeRates\Customer\Rate\Contract\CustomerRateQueryRepositoryInterface;
use Maatify\ExchangeRates\Customer\Rate\Infrastructure\Repository\PdoCustomerRateQueryRepository;
use Maatify\ExchangeRates\Customer\Rate\Service\CustomerRateQueryService;
use Maatify\ExchangeRates\Shared\Infrastructure\Persistence\Support\ScopedOrderingManager;
use Maatify\ExchangeRates\Shared\Infrastructure\Support\RateHistoryWriter;
use PDO;
use Psr\Container\ContainerInterface;

final class ExchangeRatesBindings
{
    /** @param ContainerBuilder<Container> $builder */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            // ── Shared ────────────────────────────────────────────────────

            ScopedOrderingManager::class => static function (): ScopedOrderingManager {
                return new ScopedOrderingManager();
            },

            RateHistoryWriter::class => static function (ContainerInterface $c): RateHistoryWriter {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new RateHistoryWriter($pdo);
            },

            // ── Admin — Provider ──────────────────────────────────────────

            ProviderCommandRepositoryInterface::class => static function (ContainerInterface $c): PdoProviderCommandRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                /** @var ScopedOrderingManager $orderingManager */
                $orderingManager = $c->get(ScopedOrderingManager::class);
                return new PdoProviderCommandRepository($pdo, $orderingManager);
            },

            ProviderQueryRepositoryInterface::class => static function (ContainerInterface $c): PdoProviderQueryRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoProviderQueryRepository($pdo);
            },

            ProviderCommandService::class => static function (ContainerInterface $c): ProviderCommandService {
                /** @var ProviderCommandRepositoryInterface $commandRepo */
                $commandRepo = $c->get(ProviderCommandRepositoryInterface::class);
                return new ProviderCommandService($commandRepo);
            },

            ProviderQueryService::class => static function (ContainerInterface $c): ProviderQueryService {
                /** @var ProviderQueryRepositoryInterface $queryRepo */
                $queryRepo = $c->get(ProviderQueryRepositoryInterface::class);
                return new ProviderQueryService($queryRepo);
            },

            // ── Admin — Rate ──────────────────────────────────────────────

            RateQueryRepositoryInterface::class => static function (ContainerInterface $c): PdoRateQueryRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoRateQueryRepository($pdo);
            },

            RateCommandRepositoryInterface::class => static function (ContainerInterface $c): PdoRateCommandRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                /** @var RateHistoryWriter $historyWriter */
                $historyWriter = $c->get(RateHistoryWriter::class);
                /** @var ScopedOrderingManager $orderingManager */
                $orderingManager = $c->get(ScopedOrderingManager::class);
                return new PdoRateCommandRepository($pdo, $historyWriter, $orderingManager);
            },

            RateCommandService::class => static function (ContainerInterface $c): RateCommandService {
                /** @var RateCommandRepositoryInterface $commandRepo */
                $commandRepo = $c->get(RateCommandRepositoryInterface::class);
                /** @var RateQueryRepositoryInterface $queryRepo */
                $queryRepo = $c->get(RateQueryRepositoryInterface::class);
                /** @var ProviderQueryRepositoryInterface $providerQueryRepo */
                $providerQueryRepo = $c->get(ProviderQueryRepositoryInterface::class);
                return new RateCommandService($commandRepo, $queryRepo, $providerQueryRepo);
            },

            RateQueryService::class => static function (ContainerInterface $c): RateQueryService {
                /** @var RateQueryRepositoryInterface $queryRepo */
                $queryRepo = $c->get(RateQueryRepositoryInterface::class);
                return new RateQueryService($queryRepo);
            },

            // ── Admin — RateHistory ───────────────────────────────────────

            RateHistoryQueryRepositoryInterface::class => static function (ContainerInterface $c): PdoRateHistoryQueryRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoRateHistoryQueryRepository($pdo);
            },

            RateHistoryQueryService::class => static function (ContainerInterface $c): RateHistoryQueryService {
                /** @var RateHistoryQueryRepositoryInterface $queryRepo */
                $queryRepo = $c->get(RateHistoryQueryRepositoryInterface::class);
                return new RateHistoryQueryService($queryRepo);
            },

            // ── Customer — Rate ───────────────────────────────────────────

            CustomerRateQueryRepositoryInterface::class => static function (ContainerInterface $c): PdoCustomerRateQueryRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);
                return new PdoCustomerRateQueryRepository($pdo);
            },

            CustomerRateQueryService::class => static function (ContainerInterface $c): CustomerRateQueryService {
                /** @var CustomerRateQueryRepositoryInterface $queryRepo */
                $queryRepo = $c->get(CustomerRateQueryRepositoryInterface::class);
                return new CustomerRateQueryService($queryRepo);
            },

        ]);
    }
}
