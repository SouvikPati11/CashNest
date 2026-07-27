<?php
/**
 * Fraud review queue with resolution actions.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$rows = is_array($v['rows'] ?? null) ? $v['rows'] : [];
$csrf = (string) ($v['csrf'] ?? '');
?>
<h1>Fraud Management</h1>
<div class="toolbar">
    <form method="get" action="/admin/fraud">
        <select name="status" onchange="this.form.submit()">
            <?php foreach (['' => 'All', 'open' => 'Open', 'reviewing' => 'Reviewing', 'confirmed' => 'Confirmed', 'dismissed' => 'Dismissed'] as $value => $label): ?>
                <option value="<?= $view->e($value) ?>" <?= ($v['status'] ?? '') === $value ? 'selected' : '' ?>>
                    <?= $view->e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<div class="panel">
    <table>
        <thead><tr><th>ID</th><th>User</th><th>Type</th><th>Severity</th><th>Status</th><th>Resolve</th></tr></thead>
        <tbody>
        <?php if ($rows === []): ?><tr><td colspan="6">No flags found.</td></tr><?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <?php $id = (string) ($row['id'] ?? ''); ?>
            <tr>
                <td><?= $view->e($id) ?></td>
                <td><?= $view->e($row['user_id'] ?? '') ?></td>
                <td><?= $view->e($row['flag_type'] ?? '') ?></td>
                <td><span class="chip"><?= $view->e($row['severity'] ?? '') ?></span></td>
                <td><span class="chip"><?= $view->e($row['status'] ?? '') ?></span></td>
                <td>
                    <form method="post" action="/admin/fraud/<?= $view->e($id) ?>/resolve">
                        <input type="hidden" name="_token" value="<?= $view->e($csrf) ?>">
                        <select name="status">
                            <?php foreach (['reviewing', 'confirmed', 'dismissed'] as $status): ?>
                                <option value="<?= $view->e($status) ?>"><?= $view->e(ucfirst($status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="action">
                            <?php foreach (['none', 'warned', 'withdrawals_held', 'suspended', 'banned'] as $action): ?>
                                <option value="<?= $view->e($action) ?>"><?= $view->e(str_replace('_', ' ', $action)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn" type="submit">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $view->partial('components/pagination', ['paginator' => $v['paginator'] ?? [], 'base' => '/admin/fraud?status=' . urlencode((string) ($v['status'] ?? ''))]) ?>
