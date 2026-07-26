<?php
/**
 * Reports — available CSV exports.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$reports = is_array($v['reports'] ?? null) ? $v['reports'] : [];
?>
<h1>Reports</h1>
<div class="panel">
    <table>
        <thead><tr><th>Report</th><th>Export</th></tr></thead>
        <tbody>
        <?php foreach ($reports as $key => $title): ?>
            <tr>
                <td><?= $view->e($title) ?></td>
                <td><a class="btn" href="/admin/reports/<?= $view->e($key) ?>/export">Download CSV</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
