<?php

declare(strict_types=1);

namespace Maatify\Geo\Exception;

/**
 * Marker interface — the umbrella type for every exception thrown by the
 * Geo module.
 *
 * All concrete geo exceptions implement this interface AND extend the
 * appropriate Maatify exception family class, giving callers two options:
 *
 *   catch (GeoExceptionInterface $e)       // catch ANY geo exception
 *   catch (CountryNotFoundException $e)    // catch a specific one
 *
 * The Maatify parent class determines the HTTP status and error category;
 * this interface provides module-level grouping without breaking that taxonomy.
 */
interface GeoExceptionInterface extends \Throwable {}
