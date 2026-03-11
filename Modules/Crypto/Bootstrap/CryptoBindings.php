<?php

declare(strict_types=1);

namespace Maatify\Crypto\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

/**
 * Registers all Crypto module service bindings
 * into a DI ContainerBuilder.
 *
 * --------------------------------------------------------------------------
 * PURPOSE
 * --------------------------------------------------------------------------
 * This class acts as the Composition Root adapter for the
 * Crypto module.
 *
 * It defines how crypto contracts (interfaces) are mapped
 * to their concrete implementations.
 *
 * --------------------------------------------------------------------------
 * DESIGN PRINCIPLES
 * --------------------------------------------------------------------------
 * - The module remains container-agnostic.
 * - No dependency on AdminKernel.
 * - No persistence layer assumptions.
 * - Safe for extraction as a standalone library.
 *
 * --------------------------------------------------------------------------
 * HOST CUSTOMIZATION
 * --------------------------------------------------------------------------
 * A host application MAY:
 *
 * - Override the default KeyProviderInterface implementation
 * - Replace the KeyRotationPolicyInterface if required
 * - Provide a custom PasswordHasherInterface
 *
 * Example:
 *
 *   CryptoBindings::register($builder);
 *   $builder->addDefinitions([
 *       KeyProviderInterface::class => CustomKeyProvider::class,
 *   ]);
 *
 * --------------------------------------------------------------------------
 * IMPORTANT
 * --------------------------------------------------------------------------
 * This class contains NO business logic.
 * It is strictly responsible for dependency wiring.
 *
 * Any modification here affects module composition only.
 */
final class CryptoBindings
{
    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            \Maatify\Crypto\HKDF\HKDFService::class => function (ContainerInterface $c) {
                return new \Maatify\Crypto\HKDF\HKDFService();
            },
            \Maatify\Crypto\Password\PasswordHasherInterface::class => function (ContainerInterface $c) {
                $argonPolicy = new \Maatify\Crypto\Password\DTO\ArgonPolicyDTO(
                    memoryCost: PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
                    timeCost: PASSWORD_ARGON2_DEFAULT_TIME_COST,
                    threads: PASSWORD_ARGON2_DEFAULT_THREADS
                );
                if ($c->has(\Maatify\Crypto\Password\Pepper\PasswordPepperProviderInterface::class)) {
                    $pepperProvider = $c->get(\Maatify\Crypto\Password\Pepper\PasswordPepperProviderInterface::class);
                    assert($pepperProvider instanceof \Maatify\Crypto\Password\Pepper\PasswordPepperProviderInterface);
                    return new \Maatify\Crypto\Password\PasswordHasher($pepperProvider, $argonPolicy);
                }
                // Provide a dummy pepper provider if none exists, as it's required by the constructor
                // Though, ideally the host application should provide it.
                // We could also throw an exception or create an empty pepper provider.
                // Wait, PasswordHasher constructor REQUIRES it.
                // Let's create an anonymous class for a dummy provider if none provided.
                $dummyPepper = new class implements \Maatify\Crypto\Password\Pepper\PasswordPepperProviderInterface {
                    public function getPepper(): string { return ''; }
                };
                return new \Maatify\Crypto\Password\PasswordHasher($dummyPepper, $argonPolicy);
            },
            \Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry::class => function (ContainerInterface $c) {
                return new \Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry();
            },
            \Maatify\Crypto\DX\CryptoContextFactory::class => function (ContainerInterface $c) {
                $rotation = $c->get(\Maatify\Crypto\KeyRotation\KeyRotationService::class);
                assert($rotation instanceof \Maatify\Crypto\KeyRotation\KeyRotationService);
                $hkdf = $c->get(\Maatify\Crypto\HKDF\HKDFService::class);
                assert($hkdf instanceof \Maatify\Crypto\HKDF\HKDFService);
                $registry = $c->get(\Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry::class);
                assert($registry instanceof \Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry);
                return new \Maatify\Crypto\DX\CryptoContextFactory($rotation, $hkdf, $registry);
            },
            \Maatify\Crypto\DX\CryptoDirectFactory::class => function (ContainerInterface $c) {
                $rotation = $c->get(\Maatify\Crypto\KeyRotation\KeyRotationService::class);
                assert($rotation instanceof \Maatify\Crypto\KeyRotation\KeyRotationService);
                $registry = $c->get(\Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry::class);
                assert($registry instanceof \Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry);
                return new \Maatify\Crypto\DX\CryptoDirectFactory($rotation, $registry);
            },
            \Maatify\Crypto\DX\CryptoProvider::class => function (ContainerInterface $c) {
                $contextFactory = $c->get(\Maatify\Crypto\DX\CryptoContextFactory::class);
                assert($contextFactory instanceof \Maatify\Crypto\DX\CryptoContextFactory);
                $directFactory = $c->get(\Maatify\Crypto\DX\CryptoDirectFactory::class);
                assert($directFactory instanceof \Maatify\Crypto\DX\CryptoDirectFactory);
                return new \Maatify\Crypto\DX\CryptoProvider($contextFactory, $directFactory);
            },
        ]);
    }
}
