<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Security\Password\PasswordPepperRing;
use App\Domain\Service\PasswordService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PasswordServiceTest extends TestCase
{
    /** @var array<string, string> */
    private array $peppers = [
        'v1' => 'secret-v1-must-be-long-enough-technically-32-chars',
        'v2' => 'secret-v2-must-be-long-enough-technically-32-chars'
    ];
    private string $activeId = 'v2';
    private PasswordService $service;

    protected function setUp(): void
    {
        $ring = new PasswordPepperRing($this->peppers, $this->activeId);
        $argonOptions = ['memory_cost' => 1024, 'time_cost' => 2, 'threads' => 2];
        $this->service = new PasswordService($ring, $argonOptions);
    }

    public function testHashUsesActivePepper(): void
    {
        $password = 'secret123';
        $result = $this->service->hash($password);

        $this->assertSame($this->activeId, $result['pepper_id']);
        $this->assertNotEmpty($result['hash']);
    }

    public function testVerifySuccessWithCorrectPepperId(): void
    {
        $password = 'secret123';
        $result = $this->service->hash($password);

        $this->assertTrue($this->service->verify($password, $result['hash'], $result['pepper_id']));
    }

    public function testVerifyFailsWithWrongPassword(): void
    {
        $password = 'secret123';
        $result = $this->service->hash($password);

        $this->assertFalse($this->service->verify('wrong', $result['hash'], $result['pepper_id']));
    }

    public function testVerifyFailsWithUnknownPepperId(): void
    {
        $password = 'secret123';
        $result = $this->service->hash($password);

        // Pass a pepper ID that doesn't exist in the map
        $this->assertFalse($this->service->verify($password, $result['hash'], 'unknown-id'));
    }

    public function testVerifyWorksWithInactiveButValidPepper(): void
    {
        // Create a service where v1 is active
        $ringV1 = new PasswordPepperRing($this->peppers, 'v1');
        $argonOptions = ['memory_cost' => 1024, 'time_cost' => 2, 'threads' => 2];
        $serviceV1 = new PasswordService($ringV1, $argonOptions);

        $resultV1 = $serviceV1->hash('secret123');

        // Verify using the main service (where v2 is active)
        // It should still verify because v1 is in the map (just inactive)
        $this->assertTrue($this->service->verify('secret123', $resultV1['hash'], 'v1'));
    }

    public function testNeedsRehash(): void
    {
        // Active is v2
        $password = 'secret123';
        $result = $this->service->hash($password);

        // Same pepper, should be false (assuming argon options match)
        $this->assertFalse($this->service->needsRehash($result['hash'], 'v2'));

        // Different pepper (v1), should be true
        $this->assertTrue($this->service->needsRehash($result['hash'], 'v1'));
    }

    public function testConstructorThrowsOnEmptyPeppers(): void
    {
        $this->expectException(RuntimeException::class);
        new PasswordPepperRing([], 'v1');
    }

    public function testConstructorThrowsOnMissingActiveId(): void
    {
        $this->expectException(RuntimeException::class);
        new PasswordPepperRing(['v1' => 'secret-must-be-long-enough-technically-32-chars'], 'v2');
    }
}
