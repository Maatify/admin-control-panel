<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

/**
 * Class: AppSettingNotFoundException
 *
 * Thrown when a requested setting does not exist
 * or is inactive.
 */
final class AppSettingNotFoundException extends AppSettingException
{
}
