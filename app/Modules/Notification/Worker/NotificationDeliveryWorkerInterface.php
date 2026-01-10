<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 17:40
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Notification\Worker;

/**
 * NotificationDeliveryWorkerInterface
 *
 * Defines the lifecycle of the notification delivery worker.
 */
interface NotificationDeliveryWorkerInterface
{
    /**
     * Process pending delivery queue items.
     *
     * @param   int  $limit  Max number of items to process in one run
     */
    public function run(int $limit = 50): void;
}
