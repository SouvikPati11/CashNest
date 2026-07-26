<?php
/**
 * Withdrawals review queue with approve / reject / pay actions.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$rows = is_array($v['rows'] ?? null) ? $v['rows'] : [];
$csrf = (string) ($v['csrf'] ?? '');
?>
<h1>Withdrawals</h1>
<?= $view->partial('components/filters', ['action' => '/admin/withdrawals', 'search' => $v['search'] ?? '', 'export' => '/admin/withdrawals/export']) ?>
<div class="panel">
    <table>
        <thead><tr><th>ID</th><th>User</th><th>Coins</th><th>Net</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($rows === []): ?><tr><td colspan="6">No withdrawals found.</td></tr><?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <?php $id = (string) ($row['id'] ?? ''); $status = (string) ($row['status'] ?? ''); ?>
            <tr>
                <td><?= $view->e($id) ?></td>
                <td><?= $view->e($row['user_id'] ?? '') ?></td>
                <td><?= $view->e($row['coins_amount'] ?? '') ?></td>
                <td><?= $view->e($row['net_amount'] ?? '') ?></td>
                <td><span class="chip"><?= $view->e($status) ?></span></td>
                <td>
                    <?php if ($status === 'pending'): ?>
                        <form method="post" action="/admin/withdrawals/<?= $view->e($id) ?>/approve" style="display:inline">
                            <input type="hidden" name="_token" value="<?= $view->e($csrf) ?>">
                            <button class="btn" type="submit">Approve</button>
                        </form>
                        <form method="post" action="/admin/withdrawals/<?= $view->e($id) ?>/reject" style="display:inline">
                            <input type="hidden" name="_token" value="<?= $view->e($csrf) ?>">
                            <button class="btn danger" type="submit">Reject</button>
                        </form>
                    <?php elseif ($status === 'approved'): ?>
                        <form method="post" action="/admin/withdrawals/<?= $view->e($id) ?>/pay" style="display:inline">
                            <input type="hidden" name="_token" value="<?= $view->e($csrf) ?>">
                            <button class="btn" type="submit">Mark paid</button>
                        </form>
                    <?php else: ?>
                        <span class="chip">&mdash;</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $view->partial('components/pagination', ['paginator' => $v['paginator'] ?? [], 'base' => '/admin/withdrawals?q=' . urlencode((string) ($v['search'] ?? ''))]) ?>
