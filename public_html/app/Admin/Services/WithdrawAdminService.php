<?php

declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Support\Paginator;
use App\Contracts\AdminQueryRepositoryInterface;
use App\Contracts\WithdrawSettlementServiceInterface;

/**
 * Withdrawal management for the admin panel.
 *
 * Lists payout requests and drives the review workflow by delegating to the
 * existing WithdrawSettlementService (all coin movement stays in the ledger —
 * this module never touches balances). Each action is audit-logged.
 */
final class WithdrawAdminService
{
    private const SEARCH_COLUMNS = ['uuid', 'status'];

    public function __construct(
        private AdminQueryRepositoryInterface $query,
        private WithdrawSettlementServiceInterface $settlement,
        private AuditLogService $audit
    ) {
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, paginator: Paginator, search: string}
     */
    public function list(?string $search, int $page, int $perPage): array
    {
        $search = $search !== null ? trim($search) : '';
        $term   = $search !== '' ? $search : null;

        $total     = $this->query->countRows('withdraw_requests', self::SEARCH_COLUMNS, $term);
        $paginator = new Paginator($total, $perPage, $page);
        $rows      = $this->query->paginate(
            'withdraw_requests',
            self::SEARCH_COLUMNS,
            $term,
            $paginator->perPage,
            $paginator->offset()
        );

        return ['rows' => $rows, 'paginator' => $paginator, 'search' => $search];
    }

    public function approve(int $adminId, int $requestId, string $ip): void
    {
        $this->settlement->approve($requestId, $adminId);
        $this->log($adminId, 'withdraw.approve', $requestId, $ip);
    }

    public function reject(int $adminId, int $requestId, ?string $note, string $ip): void
    {
        $this->settlement->reject($requestId, $adminId, $note);
        $this->log($adminId, 'withdraw.reject', $requestId, $ip);
    }

    public function pay(int $adminId, int $requestId, ?string $reference, string $ip): void
    {
        $this->settlement->markPaid($requestId, $adminId, $reference);
        $this->log($adminId, 'withdraw.pay', $requestId, $ip);
    }

    private function log(int $adminId, string $action, int $requestId, string $ip): void
    {
        $this->audit->log($adminId, $action, [
            'target_type' => 'withdraw_requests',
            'target_id'   => $requestId,
            'ip'          => $ip,
        ]);
    }
}
