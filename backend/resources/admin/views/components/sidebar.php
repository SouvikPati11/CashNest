<?php
/**
 * Sidebar navigation, grouped, permission-filtered upstream.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$nav    = is_array($v['nav'] ?? null) ? $v['nav'] : [];
$active = (string) ($v['active'] ?? '');
$groups = [];
foreach ($nav as $item) {
    $groups[(string) ($item['group'] ?? 'General')][] = $item;
}
?>
<aside class="sidebar">
    <div class="brand"><?= $view->e($v['brand'] ?? 'Admin') ?></div>
    <?php foreach ($groups as $group => $items): ?>
        <div class="group"><?= $view->e($group) ?></div>
        <?php foreach ($items as $item): ?>
            <?php $isActive = ($item['key'] ?? '') === $active ? ' active' : ''; ?>
            <a class="nav<?= $isActive ?>" href="<?= $view->e($item['path'] ?? '#') ?>">
                <?= $view->e($item['label'] ?? '') ?>
            </a>
        <?php endforeach; ?>
    <?php endforeach; ?>
</aside>
