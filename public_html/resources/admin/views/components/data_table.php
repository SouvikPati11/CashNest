<?php
/**
 * Generic data table.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$columns = is_array($v['columns'] ?? null) ? $v['columns'] : [];
$rows    = is_array($v['rows'] ?? null) ? $v['rows'] : [];
?>
<div class="panel">
    <table>
        <thead>
        <tr>
            <?php foreach ($columns as $column): ?>
                <th><?= $view->e(ucwords(str_replace('_', ' ', (string) $column))) ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php if ($rows === []): ?>
            <tr><td colspan="<?= $view->e(max(1, count($columns))) ?>">No records found.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($columns as $column): ?>
                    <td><?= $view->e(is_scalar($row[$column] ?? null) ? (string) $row[$column] : '') ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
