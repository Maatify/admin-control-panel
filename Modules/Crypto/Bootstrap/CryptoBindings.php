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
                if ($c->has(\Maatify\Crypto\Password\Pepper\PasswordPepperProviderInterface::class)) {
                    $pepperProvider = $c->get(\Maatify\Crypto\Password\Pepper\PasswordPepperProviderInterface::class);
                    return new \Maatify\Crypto\Password\PasswordHasher($pepperProvider);
                }
                return new \Maatify\Crypto\Password\PasswordHasher();
            },
            \Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmInterface::class => function (ContainerInterface $c) {
                return new \Maatify\Crypto\Reversible\Algorithms\SodiumAeadXchacha20poly1305Ietf();
            },
            \Maatify\Crypto\DX\CryptoContextFactory::class => function (ContainerInterface $c) {
                $rotation = $c->get(\Maatify\Crypto\KeyRotation\KeyRotationService::class);
                $hkdf = $c->get(\Maatify\Crypto\HKDF\HKDFService::class);
                $algorithm = $c->get(\Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmInterface::class);
                return new \Maatify\Crypto\DX\CryptoContextFactory($rotation, $hkdf, $algorithm);
            },
            \Maatify\Crypto\DX\CryptoDirectFactory::class => function (ContainerInterface $c) {
                $rotation = $c->get(\Maatify\Crypto\KeyRotation\KeyRotationService::class);
                $algorithm = $c->get(\Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmInterface::class);
                return new \Maatify\Crypto\DX\CryptoDirectFactory($rotation, $algorithm);
            },
            \Maatify\Crypto\DX\CryptoProvider::class => function (ContainerInterface $c) {
                $contextFactory = $c->get(\Maatify\Crypto\DX\CryptoContextFactory::class);
                $directFactory = $c->get(\Maatify\Crypto\DX\CryptoDirectFactory::class);
                return new \Maatify\Crypto\DX\CryptoProvider($contextFactory, $directFactory);
            },
        ]);
    }
}
