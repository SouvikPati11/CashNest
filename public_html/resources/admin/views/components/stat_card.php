<?php
/**
 * Single metric stat card.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
?>
<div class="card">
    <div class="label"><?= $view->e($v['label'] ?? '') ?></div>
    <div class="value"><?= $view->e(number_format((float) ($v['value'] ?? 0))) ?></div>
</div>
