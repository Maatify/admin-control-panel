<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

use Maatify\Exceptions\Exception\BusinessRule\BusinessRuleMaatifyException;

abstract class AppSettingException
    extends BusinessRuleMaatifyException
{
}