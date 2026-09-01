<?php

declare(strict_types=1);

namespace Maatify\Storage\Exception;

/**
 * Base exception for the Storage module.
 *
 * Per Maatify Module Building Standard section 6.
 */
class StorageException extends \RuntimeException
    implements StorageExceptionInterface
{}
