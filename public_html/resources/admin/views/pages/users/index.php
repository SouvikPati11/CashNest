<?php
/**
 * Users list — search, table, pagination, CSV export.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$rows = is_array($v['rows'] ?? null) ? $v['rows'] : [];

/** Map an account status to an existing chip modifier class. */
$statusChip = static function (string $status): string {
    return ['active' => 'ok', 'suspended' => 'warn', 'banned' => 'bad', 'deleted' => 'bad'][$status] ?? '';
};
?>
<h1>Users</h1>
<?= $view->partial('components/filters', ['action' => '/admin/users', 'search' => $v['search'] ?? '', 'export' => '/admin/users/export']) ?>
<div class="panel">
    <table>
        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Coins</th><th>Status</th><th>Joined</th><th></th></tr></thead>
        <tbody>
        <?php if ($rows === []): ?><tr><td colspan="7">No users found.</td></tr><?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= $view->e($row['id'] ?? '') ?></td>
                <td><?= $view->e($row['name'] ?? '') ?></td>
                <td><?= $view->e($row['email'] ?? '') ?></td>
                <td><?= $view->e(number_format((float) ($row['coin_balance_cache'] ?? 0))) ?></td>
                <td><span class="chip <?= $view->e($statusChip((string) ($row['status'] ?? ''))) ?>"><?= $view->e($row['status'] ?? '') ?></span></td>
                <td><?= $view->e($row['created_at'] ?? '') ?></td>
                <td><a class="btn tonal" href="/admin/users/<?= $view->e($row['id'] ?? '') ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $view->partial('components/pagination', ['paginator' => $v['paginator'] ?? [], 'base' => '/admin/users?q=' . urlencode((string) ($v['search'] ?? ''))]) ?>
