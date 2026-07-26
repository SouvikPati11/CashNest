<?php
/**
 * Admin layout shell — Material 3 inspired, responsive, with sidebar + topbar.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $view->e($v['brand'] ?? 'Admin') ?></title>
    <style>
        :root {
            --m3-primary: #4f46e5; --m3-on-primary: #fff; --m3-surface: #fff;
            --m3-bg: #f4f5fb; --m3-on-surface: #1b1b1f; --m3-outline: #e2e3ec;
            --m3-muted: #5f6076; --m3-danger: #b3261e; --m3-ok: #146c2e; --radius: 16px;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: var(--m3-bg); color: var(--m3-on-surface); }
        a { color: inherit; text-decoration: none; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 264px; background: var(--m3-surface); border-right: 1px solid var(--m3-outline);
            position: sticky; top: 0; height: 100vh; overflow-y: auto; flex-shrink: 0; }
        .sidebar .brand { font-weight: 700; font-size: 1.15rem; padding: 20px 22px; color: var(--m3-primary); }
        .sidebar .group { padding: 8px 22px 4px; font-size: .7rem; letter-spacing: .08em;
            text-transform: uppercase; color: var(--m3-muted); }
        .sidebar a.nav { display: flex; gap: 12px; align-items: center; padding: 11px 22px; font-size: .92rem;
            color: var(--m3-on-surface); border-radius: 0 24px 24px 0; margin-right: 12px; }
        .sidebar a.nav:hover { background: #eef0fb; }
        .sidebar a.nav.active { background: #e5e7fb; color: var(--m3-primary); font-weight: 600; }
        .main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .topbar { display: flex; justify-content: space-between; align-items: center; padding: 14px 24px;
            background: var(--m3-surface); border-bottom: 1px solid var(--m3-outline); position: sticky; top: 0; z-index: 5; }
        .topbar .who { font-size: .9rem; color: var(--m3-muted); }
        .content { padding: 24px; max-width: 1200px; }
        h1 { font-size: 1.5rem; margin: 0 0 4px; } h2 { font-size: 1.1rem; }
        .cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 16px; margin: 18px 0; }
        .card { background: var(--m3-surface); border: 1px solid var(--m3-outline); border-radius: var(--radius); padding: 18px; }
        .card .label { color: var(--m3-muted); font-size: .8rem; } .card .value { font-size: 1.9rem; font-weight: 700; }
        .panel { background: var(--m3-surface); border: 1px solid var(--m3-outline); border-radius: var(--radius);
            padding: 18px; margin: 16px 0; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--m3-outline); white-space: nowrap; }
        th { color: var(--m3-muted); font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; }
        .btn { display: inline-block; background: var(--m3-primary); color: var(--m3-on-primary); border: none;
            padding: 9px 16px; border-radius: 20px; font-size: .85rem; cursor: pointer; }
        .btn.tonal { background: #e5e7fb; color: var(--m3-primary); }
        .btn.danger { background: #fce8e6; color: var(--m3-danger); }
        .chip { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: .75rem; background: #eef0fb; color: var(--m3-muted); }
        .chip.ok { background: #e6f4ea; color: var(--m3-ok); } .chip.warn { background: #fef7e0; color: #8a6d00; }
        .chip.bad { background: #fce8e6; color: var(--m3-danger); }
        .toolbar { display: flex; gap: 10px; align-items: center; justify-content: space-between; flex-wrap: wrap; margin: 14px 0; }
        input[type=text], input[type=search], input[type=email], input[type=password], select {
            padding: 9px 12px; border: 1px solid var(--m3-outline); border-radius: 10px; font-size: .9rem; }
        .flash { padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; }
        .flash.success { background: #e6f4ea; color: var(--m3-ok); } .flash.error { background: #fce8e6; color: var(--m3-danger); }
        .flash.info { background: #eef0fb; color: var(--m3-primary); }
        .pagination { display: flex; gap: 8px; align-items: center; margin-top: 14px; font-size: .85rem; }
        .bars { display: flex; align-items: flex-end; gap: 18px; height: 180px; padding: 10px 4px; }
        .bars .bar { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; }
        .bars .bar .fill { width: 100%; background: var(--m3-primary); border-radius: 8px 8px 0 0; min-height: 4px; }
        .bars .bar .cap { font-size: .75rem; color: var(--m3-muted); }
        @media (max-width: 860px) { .sidebar { position: fixed; transform: translateX(-100%); z-index: 20; }
            .sidebar.open { transform: none; } .content { padding: 16px; } }
    </style>
</head>
<body>
<div class="layout">
    <?= $view->partial('components/sidebar', ['nav' => $v['nav'] ?? [], 'active' => $v['active'] ?? '', 'brand' => $v['brand'] ?? '']) ?>
    <div class="main">
        <?= $view->partial('components/topbar', ['admin' => $v['admin'] ?? null, 'csrf' => $v['csrf'] ?? '']) ?>
        <div class="content">
            <?= $view->partial('components/flash', ['flash' => $v['flash'] ?? null]) ?>
            <?= $v['content'] ?? '' ?>
        </div>
    </div>
</div>
</body>
</html>
