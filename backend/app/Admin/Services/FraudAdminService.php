<?php

declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Support\Paginator;
use App\Contracts\AdminFraudRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

/**
 * Fraud management for the admin panel — review and resolve fraud flags.
 */
final class FraudAdminService
{
    private const STATUSES = ['open', 'reviewing', 'confirmed', 'dismissed'];
    private const ACTIONS  = ['none', 'warned', 'withdrawals_held', 'suspended', 'banned'];

    public function __construct(
        private AdminFraudRepositoryInterface $fraud,
        private AuditLogService $audit
    ) {
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, paginator: Paginator, status: string}
     */
    public function list(?string $status, int $page, int $perPage): array
    {
        $status = $status !== null && in_array($status, self::STATUSES, true) ? $status : '';
        $filter = $status !== '' ? $status : null;

        $total     = $this->fraud->countFiltered($filter);
        $paginator = new Paginator($total, $perPage, $page);
        $rows      = $this->fraud->paginate($filter, $paginator->perPage, $paginator->offset());

        return ['rows' => $rows, 'paginator' => $paginator, 'status' => $status];
    }

    public function resolve(int $adminId, int $flagId, string $status, string $action, string $ip): void
    {
        if (!in_array($status, self::STATUSES, true) || !in_array($action, self::ACTIONS, true)) {
            throw new ValidationException(['status' => ['Invalid resolution.']]);
        }

        if ($this->fraud->find($flagId) === null) {
            throw new NotFoundException('Fraud flag not found.');
        }

        $this->fraud->resolve($flagId, $status, $action, $adminId, gmdate('Y-m-d H:i:s'));

        $this->audit->log($adminId, 'fraud.resolve', [
            'target_type' => 'fraud_flags',
            'target_id'   => $flagId,
            'after'       => ['status' => $status, 'action_taken' => $action],
            'ip'          => $ip,
        ]);
    }
}
