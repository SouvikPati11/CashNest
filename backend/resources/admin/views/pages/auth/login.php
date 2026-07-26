<?php
/**
 * Admin login (standalone page — not wrapped in the app layout).
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$twoFactor = (bool) ($v['two_factor'] ?? false);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $view->e($v['brand'] ?? 'Admin') ?> &middot; Sign in</title>
    <style>
        body { margin: 0; font-family: 'Inter', system-ui, sans-serif; background: #f4f5fb;
            display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .box { background: #fff; border: 1px solid #e2e3ec; border-radius: 20px; padding: 32px; width: 360px; }
        .box h1 { font-size: 1.3rem; margin: 0 0 4px; color: #4f46e5; }
        .box p.sub { margin: 0 0 20px; color: #5f6076; font-size: .9rem; }
        label { display: block; font-size: .82rem; color: #5f6076; margin: 12px 0 4px; }
        input { width: 100%; padding: 11px 12px; border: 1px solid #e2e3ec; border-radius: 10px; font-size: .95rem; }
        button { width: 100%; margin-top: 20px; background: #4f46e5; color: #fff; border: none; padding: 12px;
            border-radius: 22px; font-size: .95rem; cursor: pointer; }
        .msg { padding: 10px 12px; border-radius: 10px; font-size: .85rem; margin-bottom: 12px; }
        .msg.error { background: #fce8e6; color: #b3261e; } .msg.info { background: #eef0fb; color: #4f46e5; }
    </style>
</head>
<body>
<div class="box">
    <h1><?= $view->e($v['brand'] ?? 'Admin') ?></h1>
    <p class="sub">Sign in to the admin console</p>

    <?php if (!empty($v['error'])): ?><div class="msg error"><?= $view->e($v['error']) ?></div><?php endif; ?>
    <?php if (!empty($v['info'])): ?><div class="msg info"><?= $view->e($v['info']) ?></div><?php endif; ?>

    <form method="post" action="/admin/login">
        <input type="hidden" name="_token" value="<?= $view->e($v['csrf'] ?? '') ?>">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= $view->e($v['email'] ?? '') ?>" required autofocus>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>
        <?php if ($twoFactor): ?>
            <label for="code">Authentication code</label>
            <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required>
        <?php endif; ?>
        <button type="submit"><?= $twoFactor ? 'Verify &amp; sign in' : 'Sign in' ?></button>
    </form>
</div>
</body>
</html>
