<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\Password;

use Exception;

final readonly class PasswordPepperRingConfig
{
    /**
     * @param array<string, string> $peppers
     * @param string $activeId
     * @param PasswordPepperRing $ring
     */
    private function __construct(
        private array $peppers,
        private string $activeId,
        private PasswordPepperRing $ring
    ) {
    }

    /**
     * @param array<string, mixed> $env
     * @return self
     * @throws Exception
     */
    public static function fromEnv(array $env): self
    {
        if (empty($env['PASSWORD_PEPPERS'])) {
            throw new Exception('PASSWORD_PEPPERS is required and cannot be empty.');
        }

        $rawPeppers = $env['PASSWORD_PEPPERS'];
        if (!is_string($rawPeppers)) {
             throw new Exception('PASSWORD_PEPPERS must be a string.');
        }

        /** @var mixed $peppers */
        $peppers = json_decode($rawPeppers, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($peppers) || empty($peppers)) {
            throw new Exception('PASSWORD_PEPPERS must be a non-empty JSON object map');
        }

        // Validate all values are strings
        foreach ($peppers as $id => $secret) {
            if (!is_string($secret)) {
                throw new Exception("Pepper secret for ID '$id' must be a string.");
            }
            if (strlen($secret) < 32) {
                throw new Exception("Pepper secret for ID '$id' is too short (min 32 chars).");
            }
        }

        /** @var array<string, string> $peppers */

        $activeId = $env['PASSWORD_ACTIVE_PEPPER_ID'] ?? '';
        if (!is_string($activeId) || empty($activeId)) {
            throw new Exception('PASSWORD_ACTIVE_PEPPER_ID is required.');
        }

        if (!isset($peppers[$activeId])) {
            throw new Exception("PASSWORD_ACTIVE_PEPPER_ID '$activeId' not found in PASSWORD_PEPPERS.");
        }

        // Instantiate the ring to ensure domain validation passes as well
        // (though we duplicated some checks above to provide specific error messages as requested)
        try {
            $ring = new PasswordPepperRing($peppers, $activeId);
        } catch (\RuntimeException $e) {
            throw new Exception($e->getMessage(), 0, $e);
        }

        return new self($peppers, $activeId, $ring);
    }

    /**
     * @return array<string, string>
     */
    public function peppers(): array
    {
        return $this->peppers;
    }

    public function activeId(): string
    {
        return $this->activeId;
    }

    public function ring(): PasswordPepperRing
    {
        return $this->ring;
    }
}
