<?php
/**
 * Search + filter toolbar (GET form).
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$action = (string) ($v['action'] ?? '');
$search = (string) ($v['search'] ?? '');
$export = isset($v['export']) ? (string) $v['export'] : '';
?>
<div class="toolbar">
    <form method="get" action="<?= $view->e($action) ?>">
        <input type="search" name="q" value="<?= $view->e($search) ?>" placeholder="Search&hellip;">
        <button class="btn" type="submit">Search</button>
    </form>
    <?php if ($export !== ''): ?>
        <a class="btn tonal" href="<?= $view->e($export) ?>">Export CSV</a>
    <?php endif; ?>
</div>
