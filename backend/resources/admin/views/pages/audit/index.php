<?php
/**
 * Audit log browser.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$rows = is_array($v['rows'] ?? null) ? $v['rows'] : [];
?>
<h1>Audit Logs</h1>
<div class="panel">
    <table>
        <thead><tr><th>ID</th><th>Admin</th><th>Action</th><th>Target</th><th>IP</th><th>When</th></tr></thead>
        <tbody>
        <?php if ($rows === []): ?><tr><td colspan="6">No audit entries.</td></tr><?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= $view->e($row['id'] ?? '') ?></td>
                <td><?= $view->e($row['admin_id'] ?? '') ?></td>
                <td><span class="chip"><?= $view->e($row['action'] ?? '') ?></span></td>
                <td><?= $view->e(trim((string) ($row['target_type'] ?? '') . ' #' . (string) ($row['target_id'] ?? ''))) ?></td>
                <td><?= $view->e($row['ip_address'] ?? '') ?></td>
                <td><?= $view->e($row['created_at'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $view->partial('components/pagination', ['paginator' => $v['paginator'] ?? [], 'base' => '/admin/audit-logs']) ?>
