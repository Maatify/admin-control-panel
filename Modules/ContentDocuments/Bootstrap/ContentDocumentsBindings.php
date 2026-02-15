<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use PDO;
use Psr\Container\ContainerInterface;

/**
 * Registers all ContentDocuments module service bindings
 * into a DI ContainerBuilder.
 *
 * --------------------------------------------------------------------------
 * PURPOSE
 * --------------------------------------------------------------------------
 * This class acts as the Composition Root adapter for the
 * ContentDocuments module.
 *
 * It defines how domain contracts (interfaces) are mapped to their
 * infrastructure implementations (PDO repositories, services, etc.).
 *
 * --------------------------------------------------------------------------
 * DESIGN PRINCIPLES
 * --------------------------------------------------------------------------
 * - The module itself remains container-agnostic.
 * - No dependency on AdminKernel.
 * - Only relies on external contracts such as PDO and SharedCommon.
 * - Safe for extraction as a standalone library.
 *
 * --------------------------------------------------------------------------
 * HOST CUSTOMIZATION
 * --------------------------------------------------------------------------
 * A host application MAY:
 *
 * - Override any binding after calling register()
 * - Replace repositories with custom implementations
 * - Swap PDO storage layer
 * - Replace services if required
 *
 * Example:
 *
 *   ContentDocumentsBindings::register($builder);
 *   $builder->addDefinitions([
 *       DocumentRepositoryInterface::class => CustomRepository::class,
 *   ]);
 *
 * --------------------------------------------------------------------------
 * IMPORTANT
 * --------------------------------------------------------------------------
 * This class should contain NO business logic.
 * It is strictly responsible for dependency wiring.
 *
 * Any modification here affects module composition only.
 *
 *  REQUIREMENTS:
 *  The host application must provide:
 *  - PDO binding
 *  - ClockInterface binding
 */

final class ContentDocumentsBindings
{
    /**
     * @param   ContainerBuilder<Container>  $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentAcceptanceRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo*/
                $pdo = $c->get(PDO::class);
                return new \Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentAcceptanceRepository($pdo);
            },

            \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo*/
                $pdo = $c->get(PDO::class);
                return new \Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentRepository($pdo);
            },

            \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTranslationRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo*/
                $pdo = $c->get(PDO::class);
                return new \Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentTranslationRepository($pdo);
            },

            \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo*/
                $pdo = $c->get(PDO::class);
                return new \Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoDocumentTypeRepository($pdo);
            },

            \Maatify\ContentDocuments\Domain\Contract\Transaction\TransactionManagerInterface::class => function (ContainerInterface $c) {
                /** @var PDO $pdo*/
                $pdo = $c->get(PDO::class);
                return new \Maatify\ContentDocuments\Infrastructure\Persistence\MySQL\PdoTransactionManager($pdo);
            },

            \Maatify\ContentDocuments\Domain\Contract\Service\DocumentQueryServiceInterface::class => function (ContainerInterface $c) {

                /** @var \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface $documentRepository*/
                $documentRepository = $c->get(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTranslationRepositoryInterface $translationRepository*/
                $translationRepository = $c->get(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTranslationRepositoryInterface::class);

                return new \Maatify\ContentDocuments\Application\Service\DocumentQueryService($documentRepository, $translationRepository);
            },

            \Maatify\ContentDocuments\Domain\Contract\Service\DocumentLifecycleServiceInterface::class => function (ContainerInterface $c) {
                /** @var \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface $documentRepository */
                $documentRepository = $c->get(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface $documentTypeRepository */
                $documentTypeRepository = $c->get(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Transaction\TransactionManagerInterface $transactionManager */
                $transactionManager = $c->get(\Maatify\ContentDocuments\Domain\Contract\Transaction\TransactionManagerInterface::class);

                return new \Maatify\ContentDocuments\Application\Service\DocumentLifecycleService(
                    $documentRepository,
                    $documentTypeRepository,
                    $transactionManager
                );
            },

            \Maatify\ContentDocuments\Domain\Contract\Service\DocumentEnforcementServiceInterface::class => function (ContainerInterface $c) {
                /** @var \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface $documentRepository */
                $documentRepository = $c->get(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentAcceptanceRepositoryInterface $documentAcceptanceRepository */
                $documentAcceptanceRepository = $c->get(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentAcceptanceRepositoryInterface::class);

                return new \Maatify\ContentDocuments\Application\Service\DocumentEnforcementService(
                    $documentRepository,
                    $documentAcceptanceRepository
                );
            },

            \Maatify\ContentDocuments\Domain\Contract\Service\DocumentAcceptanceServiceInterface::class => function (ContainerInterface $c) {
                /** @var \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface $documentRepository */
                $documentRepository = $c->get(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentAcceptanceRepositoryInterface $documentAcceptanceRepository */
                $documentAcceptanceRepository = $c->get(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentAcceptanceRepositoryInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Transaction\TransactionManagerInterface $transactionManager */
                $transactionManager = $c->get(\Maatify\ContentDocuments\Domain\Contract\Transaction\TransactionManagerInterface::class);

                /** @var \Maatify\SharedCommon\Contracts\ClockInterface $clock */
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);

                return new \Maatify\ContentDocuments\Application\Service\DocumentAcceptanceService(
                    $documentRepository,
                    $documentAcceptanceRepository,
                    $transactionManager,
                    $clock);
            },

            \Maatify\ContentDocuments\Domain\Contract\Service\ContentDocumentsFacadeInterface::class => function (ContainerInterface $c) {
                /** @var \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface $documentRepository */
                $documentRepository = $c->get(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Service\DocumentQueryServiceInterface $queryService */
                $queryService = $c->get(\Maatify\ContentDocuments\Domain\Contract\Service\DocumentQueryServiceInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Service\DocumentAcceptanceServiceInterface $acceptanceService */
                $acceptanceService = $c->get(\Maatify\ContentDocuments\Domain\Contract\Service\DocumentAcceptanceServiceInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Service\DocumentLifecycleServiceInterface $lifecycleService */
                $lifecycleService = $c->get(\Maatify\ContentDocuments\Domain\Contract\Service\DocumentLifecycleServiceInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Service\DocumentEnforcementServiceInterface $enforcementService */
                $enforcementService = $c->get(\Maatify\ContentDocuments\Domain\Contract\Service\DocumentEnforcementServiceInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Service\DocumentTypeServiceInterface $documentTypeService */
                $documentTypeService = $c->get(\Maatify\ContentDocuments\Domain\Contract\Service\DocumentTypeServiceInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Service\DocumentTranslationServiceInterface $translationService */
                $translationService = $c->get(\Maatify\ContentDocuments\Domain\Contract\Service\DocumentTranslationServiceInterface::class);

                return new \Maatify\ContentDocuments\Application\Service\ContentDocumentsFacade(
                    $documentRepository,
                    $queryService,
                    $acceptanceService,
                    $lifecycleService,
                    $enforcementService,
                    $documentTypeService,
                    $translationService
                );
            },

            \Maatify\ContentDocuments\Domain\Contract\Service\DocumentTypeServiceInterface::class => function (ContainerInterface $c) {
                /** @var \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface $documentTypeRepository */
                $documentTypeRepository = $c->get(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface::class);
                return new \Maatify\ContentDocuments\Application\Service\DocumentTypeService($documentTypeRepository);
            },

            \Maatify\ContentDocuments\Domain\Contract\Service\DocumentTranslationServiceInterface::class => function (ContainerInterface $c) {
                /** @var \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface $documentRepository */
                $documentRepository = $c->get(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface::class);

                /** @var \Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTranslationRepositoryInterface $translationRepository */
                $translationRepository = $c->get(\Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTranslationRepositoryInterface::class);

                /** @var \Maatify\SharedCommon\Contracts\ClockInterface $clock */
                $clock = $c->get(\Maatify\SharedCommon\Contracts\ClockInterface::class);

                return new \Maatify\ContentDocuments\Application\Service\DocumentTranslationService($documentRepository, $translationRepository, $clock);
            }

        ]);
    }
}
