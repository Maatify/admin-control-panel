<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

/**
 * Base exception for all I18n module errors.
 *
 * - Thrown ONLY from Service layer
 * - NEVER from Repository layer
 */
abstract class I18nException extends \RuntimeException
{
}
