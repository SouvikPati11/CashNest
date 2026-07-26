<?php
/**
 * Admin settings — public platform settings (read-only).
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$settings = is_array($v['settings'] ?? null) ? $v['settings'] : [];
?>
<h1>Settings</h1>
<div class="panel">
    <table>
        <thead><tr><th>Key</th><th>Value</th></tr></thead>
        <tbody>
        <?php if ($settings === []): ?><tr><td colspan="2">No public settings configured.</td></tr><?php endif; ?>
        <?php foreach ($settings as $setting): ?>
            <tr>
                <td><?= $view->e($setting['key'] ?? '') ?></td>
                <td><?= $view->e(is_scalar($setting['value'] ?? null) ? (string) $setting['value'] : (string) json_encode($setting['value'] ?? null)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
