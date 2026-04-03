<?php

declare(strict_types=1);

namespace Maatify\Currency\Exception;

/**
 * Marker interface — the umbrella type for every exception thrown by the
 * Currencies module.
 *
 * All concrete currency exceptions implement this interface AND extend the
 * appropriate Maatify exception family class, giving callers two options:
 *
 *   catch (CurrencyExceptionInterface $e)   // catch ANY currency exception
 *   catch (CurrencyNotFoundException $e)    // catch a specific one
 *
 * The Maatify parent class determines the HTTP status and error category;
 * this interface provides module-level grouping without breaking that taxonomy.
 */
interface CurrencyExceptionInterface extends \Throwable {}
