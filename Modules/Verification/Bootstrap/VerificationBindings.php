<?php

declare(strict_types=1);

namespace Maatify\Verification\Bootstrap;

use DI\ContainerBuilder;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\Verification\Domain\Contracts\VerificationCodeGeneratorInterface;
use Maatify\Verification\Domain\Contracts\VerificationCodePolicyResolverInterface;
use Maatify\Verification\Domain\Contracts\VerificationCodeRepositoryInterface;
use Maatify\Verification\Domain\Contracts\VerificationCodeValidatorInterface;
use Maatify\Verification\Domain\Service\VerificationCodeGenerator;
use Maatify\Verification\Domain\Service\VerificationCodePolicyResolver;
use Maatify\Verification\Domain\Service\VerificationCodeValidator;
use Maatify\Verification\Infrastructure\Repository\PdoVerificationCodeRepository;
use PDO;
use Psr\Container\ContainerInterface;

class VerificationBindings
{
    /**
     * @param ContainerBuilder<\DI\Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            VerificationCodeRepositoryInterface::class => function (ContainerInterface $c) {
                $pdo = $c->get(PDO::class);
                $clock = $c->get(ClockInterface::class);
                assert($pdo instanceof PDO);
                assert($clock instanceof ClockInterface);

                return new PdoVerificationCodeRepository($pdo, $clock);
            },

            VerificationCodePolicyResolverInterface::class => function (ContainerInterface $c) {
                return new VerificationCodePolicyResolver();
            },

            VerificationCodeGeneratorInterface::class => function (ContainerInterface $c) {
                $repo = $c->get(VerificationCodeRepositoryInterface::class);
                $resolver = $c->get(VerificationCodePolicyResolverInterface::class);
                $clock = $c->get(ClockInterface::class);

                assert($repo instanceof VerificationCodeRepositoryInterface);
                assert($resolver instanceof VerificationCodePolicyResolverInterface);
                assert($clock instanceof ClockInterface);

                return new VerificationCodeGenerator($repo, $resolver, $clock);
            },

            VerificationCodeValidatorInterface::class => function (ContainerInterface $c) {
                $repo = $c->get(VerificationCodeRepositoryInterface::class);
                $clock = $c->get(ClockInterface::class);

                assert($repo instanceof VerificationCodeRepositoryInterface);
                assert($clock instanceof ClockInterface);

                return new VerificationCodeValidator($repo, $clock);
            },
        ]);
    }
}
