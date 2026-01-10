<?php
/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Jules
 */

declare(strict_types=1);

namespace Tests\Modules\Notification\Worker;

use App\Modules\Notification\Contract\NotificationSenderInterface;
use App\Modules\Notification\Contract\NotificationSenderRegistryInterface;
use App\Modules\Notification\Crypto\NotificationDecryptorInterface;
use App\Modules\Notification\DTO\NotificationDeliveryResultDTO;
use App\Modules\Notification\Enum\NotificationChannel;
use App\Modules\Notification\Worker\NotificationDeliveryWorker;
use DateTimeImmutable;
use Exception;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class NotificationDeliveryWorkerTest extends TestCase
{
    private PDO $pdo;
    private NotificationSenderRegistryInterface|\PHPUnit\Framework\MockObject\MockObject $registry;
    private NotificationDecryptorInterface|\PHPUnit\Framework\MockObject\MockObject $decryptor;
    private NotificationDeliveryWorker $worker;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Register MySQL NOW() function equivalent for SQLite
        $this->pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'));

        $this->createSchema();

        $this->registry = $this->createMock(NotificationSenderRegistryInterface::class);
        $this->decryptor = $this->createMock(NotificationDecryptorInterface::class);

        $this->worker = new NotificationDeliveryWorker(
            $this->pdo,
            $this->registry,
            $this->decryptor
        );
    }

    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE notification_delivery_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                status TEXT NOT NULL,
                channel_type TEXT NOT NULL,
                entity_id TEXT,
                intent_id TEXT,
                notification_type TEXT,
                priority INTEGER DEFAULT 0,
                scheduled_at DATETIME,
                recipient_encrypted TEXT,
                recipient_iv TEXT,
                recipient_tag TEXT,
                recipient_key_id TEXT,
                payload_encrypted TEXT,
                payload_iv TEXT,
                payload_tag TEXT,
                payload_key_id TEXT,
                channel_meta TEXT,
                attempts INTEGER DEFAULT 0,
                sent_at DATETIME,
                last_error TEXT,
                updated_at DATETIME,
                created_at DATETIME
            );

            CREATE TABLE admin_notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id TEXT,
                notification_type TEXT,
                channel_type TEXT,
                intent_id TEXT,
                created_at DATETIME,
                read_at DATETIME
            );
        ");
    }

    private function insertQueue(array $overrides = []): int
    {
        $default = [
            'status' => 'pending',
            'channel_type' => 'email',
            'entity_id' => 'admin-1',
            'intent_id' => 'intent-1',
            'notification_type' => 'alert',
            'priority' => 0,
            'scheduled_at' => date('Y-m-d H:i:s'),
            'recipient_encrypted' => 'enc_rec',
            'recipient_iv' => 'iv',
            'recipient_tag' => 'tag',
            'recipient_key_id' => 'k1',
            'payload_encrypted' => 'enc_pay',
            'payload_iv' => 'iv',
            'payload_tag' => 'tag',
            'payload_key_id' => 'k1',
            'channel_meta' => '{}',
            'attempts' => 0,
        ];

        $data = array_merge($default, $overrides);

        $cols = implode(', ', array_keys($data));
        $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));

        $stmt = $this->pdo->prepare("INSERT INTO notification_delivery_queue ($cols) VALUES ($vals)");
        $stmt->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Case 1: pending → sent
     */
    public function testPendingToSent(): void
    {
        $id = $this->insertQueue();

        $this->decryptor->method('decrypt')->willReturn('decrypted_value');

        $sender = $this->createMock(NotificationSenderInterface::class);
        $sender->method('send')->willReturn(new NotificationDeliveryResultDTO(
            'intent-1',
            NotificationChannel::EMAIL,
            'sent',
            new DateTimeImmutable()
        ));

        $this->registry->method('resolve')->willReturn($sender);

        $this->worker->run();

        // Verify Queue
        $stmt = $this->pdo->prepare("SELECT * FROM notification_delivery_queue WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('sent', $row['status']);
        $this->assertNotNull($row['sent_at']);
        $this->assertEquals('', $row['last_error']);

        // Verify History
        $stmt = $this->pdo->prepare("SELECT * FROM admin_notifications WHERE intent_id = ?");
        $stmt->execute(['intent-1']);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(1, $history);
        $this->assertEquals('admin-1', $history[0]['admin_id']);
        $this->assertEquals('email', $history[0]['channel_type']);
    }

    /**
     * Case 2: decrypt failure → failed
     */
    public function testDecryptFailureToFailed(): void
    {
        $id = $this->insertQueue();

        $this->decryptor->method('decrypt')->willThrowException(new RuntimeException('Decrypt error'));

        // Sender should not be called
        $this->registry->expects($this->never())->method('resolve');

        $this->worker->run();

        // Verify Queue
        $stmt = $this->pdo->prepare("SELECT * FROM notification_delivery_queue WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('failed', $row['status']);
        $this->assertEquals('decrypt_failed', $row['last_error']);

        // Verify History
        $stmt = $this->pdo->prepare("SELECT * FROM admin_notifications WHERE intent_id = ?");
        $stmt->execute(['intent-1']);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(1, $history);
    }

    /**
     * Case 3: no sender → skipped
     */
    public function testNoSenderToSkipped(): void
    {
        $id = $this->insertQueue();

        $this->decryptor->method('decrypt')->willReturn('val');

        $this->registry->method('resolve')->willThrowException(new RuntimeException('No sender'));

        $this->worker->run();

        $stmt = $this->pdo->prepare("SELECT * FROM notification_delivery_queue WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('skipped', $row['status']);
        $this->assertEquals('no_sender_for_channel', $row['last_error']);

        // Verify History
        $stmt = $this->pdo->prepare("SELECT count(*) FROM admin_notifications WHERE intent_id = ?");
        $stmt->execute(['intent-1']);
        $this->assertEquals(1, $stmt->fetchColumn());
    }

    /**
     * Case 4: sender throws → failed
     */
    public function testSenderThrowsToFailed(): void
    {
        $id = $this->insertQueue();

        $this->decryptor->method('decrypt')->willReturn('val');

        $sender = $this->createMock(NotificationSenderInterface::class);
        $sender->method('send')->willThrowException(new Exception('Network error'));

        $this->registry->method('resolve')->willReturn($sender);

        $this->worker->run();

        $stmt = $this->pdo->prepare("SELECT * FROM notification_delivery_queue WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('failed', $row['status']);
        $this->assertEquals('sender_exception', $row['last_error']);

        // Verify History
        $stmt = $this->pdo->prepare("SELECT count(*) FROM admin_notifications WHERE intent_id = ?");
        $stmt->execute(['intent-1']);
        $this->assertEquals(1, $stmt->fetchColumn());
    }

    /**
     * Case 5: invalid channel_meta → failed
     */
    public function testInvalidChannelMetaToFailed(): void
    {
        $id = $this->insertQueue(['channel_meta' => '{invalid_json']);

        $this->decryptor->method('decrypt')->willReturn('val');

        $sender = $this->createMock(NotificationSenderInterface::class);
        $this->registry->method('resolve')->willReturn($sender);

        // Sender should not be called because meta check happens before send
        $sender->expects($this->never())->method('send');

        $this->worker->run();

        $stmt = $this->pdo->prepare("SELECT * FROM notification_delivery_queue WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('failed', $row['status']);
        $this->assertEquals('invalid_channel_meta', $row['last_error']);

        // Verify History
        $stmt = $this->pdo->prepare("SELECT count(*) FROM admin_notifications WHERE intent_id = ?");
        $stmt->execute(['intent-1']);
        $this->assertEquals(1, $stmt->fetchColumn());
    }

    /**
     * Case 6: already taken row (Concurrency Simulation)
     */
    public function testAlreadyTakenRow(): void
    {
        // Insert 2 rows
        // Row 1: Scheduled earlier, so processed first
        $this->insertQueue([
            'id' => 1,
            'status' => 'pending',
            'intent_id' => 'intent-1',
            'scheduled_at' => date('Y-m-d H:i:s', strtotime('-2 minutes'))
        ]);
        // Row 2: Scheduled later (but still in past), processed second
        $this->insertQueue([
            'id' => 2,
            'status' => 'pending',
            'intent_id' => 'intent-2',
            'scheduled_at' => date('Y-m-d H:i:s', strtotime('-1 minute'))
        ]);

        $this->decryptor->method('decrypt')->willReturn('val');

        $sender = $this->createMock(NotificationSenderInterface::class);

        // We expect send() to be called for Row 1.
        // Inside this call, we modify Row 2 to simulate another worker taking it.
        $sender->expects($this->once())
            ->method('send')
            ->willReturnCallback(function () {
                // Side effect: Change status of Row 2 to 'processing' (or any non-pending status)
                // Since we are in the same transaction, this update is visible to the worker's subsequent queries.
                $this->pdo->exec("UPDATE notification_delivery_queue SET status = 'processing_external' WHERE id = 2");

                return new NotificationDeliveryResultDTO(
                    'intent-1',
                    NotificationChannel::EMAIL,
                    'sent',
                    new DateTimeImmutable()
                );
            });

        $this->registry->method('resolve')->willReturn($sender);

        $this->worker->run();

        // Verify Row 1: Sent
        $stmt = $this->pdo->prepare("SELECT status FROM notification_delivery_queue WHERE id = 1");
        $stmt->execute();
        $this->assertEquals('sent', $stmt->fetchColumn());

        // Verify Row 2: Should be 'processing_external' (the stolen state)
        // AND should NOT have been processed by the worker (no 'failed' status override, no history)
        $stmt = $this->pdo->prepare("SELECT status, last_error FROM notification_delivery_queue WHERE id = 2");
        $stmt->execute();
        $row2 = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('processing_external', $row2['status']);
        // If worker had processed it, it would fail with 'worker_exception' or similar if update failed?
        // No, logic is: update(status=processing where status=pending). If rowCount=0, return.
        // So nothing else happens.
        $this->assertNull($row2['last_error']);

        // Verify History: Row 1 has history, Row 2 does NOT
        $stmt = $this->pdo->prepare("SELECT count(*) FROM admin_notifications WHERE intent_id = 'intent-1'");
        $stmt->execute();
        $this->assertEquals(1, $stmt->fetchColumn());

        $stmt = $this->pdo->prepare("SELECT count(*) FROM admin_notifications WHERE intent_id = 'intent-2'");
        $stmt->execute();
        $this->assertEquals(0, $stmt->fetchColumn());
    }
}
