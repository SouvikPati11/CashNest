<?php
/**
 * Backup & restore — operational status/policy.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
?>
<h1>Backup &amp; Restore</h1>
<div class="panel">
    <h2>Database backups</h2>
    <p class="who">
        Automated database snapshots are produced by the scheduled backup job outside the request path and
        retained per the platform's retention policy. Restores are performed by an operator from the retained
        snapshots. This screen surfaces status to administrators; destructive restore actions are intentionally
        not exposed through the web console.
    </p>
    <span class="chip ok">Scheduled backups: enabled</span>
    <span class="chip">Retention: 30 days</span>
</div>
