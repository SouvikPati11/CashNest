<?php
/**
 * One-shot flash message.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$flash = is_array($v['flash'] ?? null) ? $v['flash'] : null;
if ($flash === null) {
    return;
}
$type = in_array($flash['type'] ?? '', ['success', 'error', 'info'], true) ? $flash['type'] : 'info';
?>
<div class="flash <?= $view->e($type) ?>"><?= $view->e($flash['message'] ?? '') ?></div>
