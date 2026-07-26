<?php

declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Support\Paginator;
use App\Contracts\AdminQueryRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\User;

/**
 * User management for the admin panel.
 *
 * Lists/searches users through the generic query repository and mutates account
 * status through the existing user repository (no existing module is modified),
 * writing an audit entry for every change.
 */
final class UserAdminService
{
    private const SEARCH_COLUMNS = ['name', 'email', 'uuid'];

    public function __construct(
        private AdminQueryRepositoryInterface $query,
        private UserRepositoryInterface $users,
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

        $total     = $this->query->countRows('users', self::SEARCH_COLUMNS, $term);
        $paginator = new Paginator($total, $perPage, $page);
        $rows      = $this->query->paginate(
            'users',
            self::SEARCH_COLUMNS,
            $term,
            $paginator->perPage,
            $paginator->offset()
        );

        return ['rows' => $rows, 'paginator' => $paginator, 'search' => $search];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundException
     */
    public function find(int $userId): array
    {
        $row = $this->users->find($userId);

        if ($row === null) {
            throw new NotFoundException('User not found.');
        }

        return $row;
    }

    /**
     * Change a user's account status (active/suspended/banned).
     */
    public function setStatus(int $adminId, int $userId, string $status, string $ip): void
    {
        if (!in_array($status, User::STATUSES, true)) {
            throw new ValidationException(['status' => ['Invalid account status.']]);
        }

        $before = $this->find($userId);

        $this->users->update($userId, ['status' => $status]);

        $this->audit->log($adminId, 'user.status', [
            'target_type' => 'users',
            'target_id'   => $userId,
            'before'      => ['status' => $before['status'] ?? null],
            'after'       => ['status' => $status],
            'ip'          => $ip,
        ]);
    }
}
