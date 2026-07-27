<?php
/**
 * Topbar with the current admin and a logout form.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$admin = is_array($v['admin'] ?? null) ? $v['admin'] : null;
$name  = $admin !== null && isset($admin['name']) ? (string) $admin['name'] : 'Administrator';
?>
<div class="topbar">
    <div class="who">Signed in as <strong><?= $view->e($name) ?></strong></div>
    <form method="post" action="/admin/logout">
        <input type="hidden" name="_token" value="<?= $view->e($v['csrf'] ?? '') ?>">
        <button class="btn tonal" type="submit">Log out</button>
    </form>
</div>
