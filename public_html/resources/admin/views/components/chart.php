<?php
/**
 * Dependency-free bar chart (CSS bars from a labels/values dataset).
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$labels = is_array($v['labels'] ?? null) ? array_values($v['labels']) : [];
$values = is_array($v['values'] ?? null) ? array_values($v['values']) : [];
$max    = 0;
foreach ($values as $value) {
    $max = max($max, (int) $value);
}
$max = $max > 0 ? $max : 1;
?>
<div class="panel">
    <h2><?= $view->e($v['title'] ?? 'Overview') ?></h2>
    <div class="bars">
        <?php foreach ($labels as $i => $label): ?>
            <?php $value = (int) ($values[$i] ?? 0); $height = (int) round(($value / $max) * 150); ?>
            <div class="bar">
                <div class="cap"><?= $view->e(number_format((float) $value)) ?></div>
                <div class="fill" style="height: <?= $view->e($height) ?>px"></div>
                <div class="cap"><?= $view->e($label) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
