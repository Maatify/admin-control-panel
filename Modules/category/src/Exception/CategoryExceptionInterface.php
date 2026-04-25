<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

/**
 * Marker interface — the umbrella type for every exception thrown by the
 * Category module.
 *
 * All concrete category exceptions implement this interface AND extend the
 * appropriate Maatify exception family class, giving callers two options:
 *
 *   catch (CategoryExceptionInterface $e)   // catch ANY category exception
 *   catch (CategoryNotFoundException $e)    // catch a specific one
 *
 * The Maatify parent class determines the HTTP status and error category;
 * this interface provides module-level grouping without breaking that taxonomy.
 */
interface CategoryExceptionInterface extends \Throwable {}

