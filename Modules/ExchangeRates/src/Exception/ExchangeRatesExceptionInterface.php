<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Exception;

/**
 * Marker interface for all ExchangeRates module exceptions.
 *
 * Every module exception implements this interface so callers
 * can catch the entire module's exception family with a single
 * catch block:
 *
 *   } catch (ExchangeRatesExceptionInterface $e) { ... }
 */
interface ExchangeRatesExceptionInterface extends \Throwable {}
