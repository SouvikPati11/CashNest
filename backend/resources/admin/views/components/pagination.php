<?php
/**
 * Pagination control.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$p       = is_array($v['paginator'] ?? null) ? $v['paginator'] : [];
$base    = (string) ($v['base'] ?? '');
$page    = (int) ($p['page'] ?? 1);
$pages   = (int) ($p['total_pages'] ?? 1);
$total   = (int) ($p['total'] ?? 0);
$hasPrev = (bool) ($p['has_previous'] ?? false);
$hasNext = (bool) ($p['has_next'] ?? false);
$sep     = str_contains($base, '?') ? '&' : '?';
?>
<div class="pagination">
    <?php if ($hasPrev): ?>
        <a class="btn tonal" href="<?= $view->e($base . $sep . 'page=' . ($page - 1)) ?>">Previous</a>
    <?php endif; ?>
    <span>Page <?= $view->e($page) ?> of <?= $view->e($pages) ?> &middot; <?= $view->e($total) ?> total</span>
    <?php if ($hasNext): ?>
        <a class="btn tonal" href="<?= $view->e($base . $sep . 'page=' . ($page + 1)) ?>">Next</a>
    <?php endif; ?>
</div>
