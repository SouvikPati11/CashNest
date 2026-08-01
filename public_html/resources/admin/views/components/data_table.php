<?php
/**
 * Generic data table.
 *
 * Optional `edit_base` (e.g. "/admin/r/reward_spin") adds a trailing Edit link
 * column pointing at "{edit_base}/{id}/edit". Omit it for read-only tables.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$columns  = is_array($v['columns'] ?? null) ? $v['columns'] : [];
$rows     = is_array($v['rows'] ?? null) ? $v['rows'] : [];
$editBase = is_string($v['edit_base'] ?? null) ? $v['edit_base'] : '';
$colspan  = max(1, count($columns) + ($editBase !== '' ? 1 : 0));
?>
<div class="panel">
    <table>
        <thead>
        <tr>
            <?php foreach ($columns as $column): ?>
                <th><?= $view->e(ucwords(str_replace('_', ' ', (string) $column))) ?></th>
            <?php endforeach; ?>
            <?php if ($editBase !== ''): ?><th></th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php if ($rows === []): ?>
            <tr><td colspan="<?= $view->e($colspan) ?>">No records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($columns as $column): ?>
                    <td><?= $view->e(is_scalar($row[$column] ?? null) ? (string) $row[$column] : '') ?></td>
                <?php endforeach; ?>
                <?php if ($editBase !== ''): ?>
                    <td><a class="btn tonal" href="<?= $view->e($editBase . '/' . (string) ($row['id'] ?? '') . '/edit') ?>">Edit</a></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
