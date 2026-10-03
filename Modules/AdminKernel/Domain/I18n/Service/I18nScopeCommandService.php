<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-10-01 00:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Service;

use Maatify\AdminKernel\Domain\Exception\EntityAlreadyExistsException;
use Maatify\AdminKernel\Domain\Exception\EntityInUseException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\Exception\InvalidOperationException;
use Maatify\I18n\Exception\ScopeNotFoundException;
use Maatify\I18n\Exception\ScopeAlreadyExistsException;
use Maatify\I18n\Exception\ScopeInUseException;
use Maatify\I18n\Management\Command\CreateScopeCommand;
use Maatify\I18n\Management\Command\UpdateScopeMetadataCommand;
use Maatify\I18n\Management\Service\I18nScopeManagementService;

/**
 * Admin scope mutations. All persistence, locking, ordering and the "in use"
 * decision belong to the I18n package; the Host only translates the package's
 * semantic exceptions into the Admin exception contract it already exposes.
 */
final readonly class I18nScopeCommandService
{
    public function __construct(
        private I18nScopeManagementService $management,
    ) {
    }

    /**
     * @throws EntityAlreadyExistsException
     */
    public function create(string $code, string $name, string $description, bool $isActive): int
    {
        try {
            return $this->management->create(new CreateScopeCommand($code, $name, $description, $isActive));
        } catch (ScopeAlreadyExistsException) {
            throw new EntityAlreadyExistsException('I18nScope', 'code', $code);
        }
    }

    /**
     * @throws EntityNotFoundException
     * @throws InvalidOperationException
     */
    public function updateMetadata(int $id, ?string $name, ?string $description): void
    {
        if ($name === null && $description === null) {
            throw new InvalidOperationException(
                'I18nScope',
                'update-metadata',
                'At least one field (name or description) must be provided'
            );
        }

        try {
            $this->management->updateMetadata(new UpdateScopeMetadataCommand($id, $name, $description));
        } catch (ScopeNotFoundException) {
            throw new EntityNotFoundException('I18nScope', (string) $id);
        }
    }

    /**
     * @throws EntityNotFoundException
     */
    public function setActive(int $id, bool $isActive): void
    {
        try {
            $this->management->setActive($id, $isActive);
        } catch (ScopeNotFoundException) {
            throw new EntityNotFoundException('I18nScope', (string) $id);
        }
    }

    /**
     * @throws EntityNotFoundException
     * @throws EntityInUseException
     * @throws EntityAlreadyExistsException
     */
    public function changeCode(int $id, string $newCode): void
    {
        try {
            $this->management->changeCode($id, $newCode);
        } catch (ScopeNotFoundException) {
            throw new EntityNotFoundException('I18nScope', (string) $id);
        } catch (ScopeInUseException $e) {
            throw new EntityInUseException('I18nScope', $e->scopeCode, 'domains or translations');
        } catch (ScopeAlreadyExistsException) {
            throw new EntityAlreadyExistsException('I18nScope', 'code', $newCode);
        }
    }

    /**
     * @throws EntityNotFoundException
     */
    public function moveToPosition(int $id, int $position): void
    {
        try {
            $this->management->moveToPosition($id, $position);
        } catch (ScopeNotFoundException) {
            throw new EntityNotFoundException('I18nScope', (string) $id);
        }
    }
}
