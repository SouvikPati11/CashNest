<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Services\AuditLogService;
use App\Admin\Services\WithdrawAdminService;
use App\Contracts\WithdrawSettlementServiceInterface;
use App\Models\WithdrawRequest;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAdminAuditLogRepository;
use Tests\Support\InMemoryAdminQueryRepository;

final class WithdrawAdminServiceTest extends TestCase
{
    private InMemoryAdminAuditLogRepository $auditRepo;

    /** @var array<int, string> */
    private array $calls = [];

    private WithdrawAdminService $service;

    protected function setUp(): void
    {
        $query = new InMemoryAdminQueryRepository();
        $query->seed('withdraw_requests', [
            ['id' => 1, 'user_id' => 10, 'status' => 'pending', 'coins_amount' => 5000, 'net_amount' => '5.0000'],
        ]);

        $this->auditRepo = new InMemoryAdminAuditLogRepository();
        $calls           = &$this->calls;

        $settlement = new class ($calls) implements WithdrawSettlementServiceInterface {
            /** @param array<int, string> $calls */
            public function __construct(private array &$calls)
            {
            }

            public function approve(int $requestId, ?int $adminId = null, ?string $note = null): WithdrawRequest
            {
                $this->calls[] = 'approve:' . $requestId . ':' . (string) $adminId;

                return WithdrawRequest::fromRow(['id' => $requestId, 'status' => 'approved']);
            }

            public function markPaid(
                int $requestId,
                ?int $adminId = null,
                ?string $externalReference = null,
                ?string $note = null
            ): WithdrawRequest {
                $this->calls[] = 'pay:' . $requestId;

                return WithdrawRequest::fromRow(['id' => $requestId, 'status' => 'paid']);
            }

            public function reject(int $requestId, ?int $adminId = null, ?string $note = null): WithdrawRequest
            {
                $this->calls[] = 'reject:' . $requestId;

                return WithdrawRequest::fromRow(['id' => $requestId, 'status' => 'rejected']);
            }
        };

        $this->service = new WithdrawAdminService($query, $settlement, new AuditLogService($this->auditRepo));
    }

    public function testListsWithdrawals(): void
    {
        $result = $this->service->list(null, 1, 20);

        self::assertCount(1, $result['rows']);
    }

    public function testApproveDelegatesAndAudits(): void
    {
        $this->service->approve(7, 1, '1.2.3.4');

        self::assertSame('approve:1:7', $this->calls[0]);
        self::assertSame('withdraw.approve', $this->auditRepo->rows[1]['action']);
    }

    public function testRejectAndPayDelegate(): void
    {
        $this->service->reject(7, 1, 'bad', '1.2.3.4');
        $this->service->pay(7, 1, 'ref-9', '1.2.3.4');

        self::assertSame('reject:1', $this->calls[0]);
        self::assertSame('pay:1', $this->calls[1]);
        self::assertSame('withdraw.reject', $this->auditRepo->rows[1]['action']);
        self::assertSame('withdraw.pay', $this->auditRepo->rows[2]['action']);
    }
}
