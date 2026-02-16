<?php

declare(strict_types=1);

namespace Maatify\Validation\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

/**
 * Registers all Validation module service bindings
 * into a DI ContainerBuilder.
 *
 * --------------------------------------------------------------------------
 * PURPOSE
 * --------------------------------------------------------------------------
 * This class acts as the Composition Root adapter for the
 * Validation module.
 *
 * It defines how validation contracts (interfaces) are mapped
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
 * - Override the default Validator implementation
 * - Replace the ValidationGuard if required
 * - Provide a custom validation engine
 *
 * Example:
 *
 *   ValidationBindings::register($builder);
 *   $builder->addDefinitions([
 *       ValidatorInterface::class => CustomValidator::class,
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

final class ValidationBindings
{
    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([

            \Maatify\Validation\Contracts\ValidatorInterface::class => function (ContainerInterface $c) {
                return new \Maatify\Validation\Validator\RespectValidator();
            },

            \Maatify\Validation\Guard\ValidationGuard::class => function (ContainerInterface $c) {
                /** @var \Maatify\Validation\Contracts\ValidatorInterface $validator */
                $validator = $c->get(\Maatify\Validation\Contracts\ValidatorInterface::class);

                return new \Maatify\Validation\Guard\ValidationGuard($validator);
            },

        ]);
    }
}
