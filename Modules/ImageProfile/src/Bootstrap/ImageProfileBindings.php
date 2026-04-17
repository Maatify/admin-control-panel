<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Maatify\ImageProfile\Contract\ImageProfileCommandRepositoryInterface;
use Maatify\ImageProfile\Contract\ImageProfileQueryReaderInterface;
use Maatify\ImageProfile\Contract\ImageProfileValidationServiceInterface;
use Maatify\ImageProfile\Infrastructure\Repository\PdoImageProfileCommandRepository;
use Maatify\ImageProfile\Infrastructure\Repository\PdoImageProfileQueryReader;
use Maatify\ImageProfile\Service\ImageProfileCommandService;
use Maatify\ImageProfile\Service\ImageProfileQueryService;
use Maatify\ImageProfile\Service\ImageProfileValidationService;
use PDO;
use Psr\Container\ContainerInterface;

final class ImageProfileBindings
{
    /** @param ContainerBuilder<Container> $builder */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            ImageProfileQueryReaderInterface::class => static function (ContainerInterface $c): PdoImageProfileQueryReader {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);

                return new PdoImageProfileQueryReader($pdo);
            },

            ImageProfileCommandRepositoryInterface::class => static function (ContainerInterface $c): PdoImageProfileCommandRepository {
                /** @var PDO $pdo */
                $pdo = $c->get(PDO::class);

                return new PdoImageProfileCommandRepository($pdo);
            },

            ImageProfileQueryService::class => static function (ContainerInterface $c): ImageProfileQueryService {
                /** @var ImageProfileQueryReaderInterface $reader */
                $reader = $c->get(ImageProfileQueryReaderInterface::class);

                return new ImageProfileQueryService($reader);
            },

            ImageProfileCommandService::class => static function (ContainerInterface $c): ImageProfileCommandService {
                /** @var ImageProfileCommandRepositoryInterface $commandRepo */
                $commandRepo = $c->get(ImageProfileCommandRepositoryInterface::class);
                /** @var ImageProfileQueryReaderInterface $reader */
                $reader = $c->get(ImageProfileQueryReaderInterface::class);

                return new ImageProfileCommandService($commandRepo, $reader);
            },

            ImageProfileValidationServiceInterface::class => static function (ContainerInterface $c): ImageProfileValidationService {
                /** @var ImageProfileQueryReaderInterface $reader */
                $reader = $c->get(ImageProfileQueryReaderInterface::class);

                return new ImageProfileValidationService($reader);
            },
        ]);
    }
}
