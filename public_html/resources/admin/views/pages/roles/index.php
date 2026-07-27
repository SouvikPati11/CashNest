<?php
/**
 * Roles & access (RBAC) — roles and their granted permissions.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$roles = is_array($v['roles'] ?? null) ? $v['roles'] : [];
?>
<h1>Roles &amp; Access</h1>
<?php foreach ($roles as $role): ?>
    <?php $perms = is_array($role['permissions'] ?? null) ? $role['permissions'] : []; ?>
    <div class="panel">
        <h2><?= $view->e($role['name'] ?? '') ?> <span class="chip"><?= $view->e($role['slug'] ?? '') ?></span></h2>
        <p class="who"><?= $view->e($role['description'] ?? '') ?></p>
        <div>
            <?php if (($role['slug'] ?? '') === 'super_admin'): ?>
                <span class="chip ok">All permissions</span>
            <?php elseif ($perms === []): ?>
                <span class="chip">No permissions</span>
            <?php else: ?>
                <?php foreach ($perms as $perm): ?>
                    <span class="chip"><?= $view->e($perm) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
