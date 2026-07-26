<?php
/**
 * Dashboard — stat cards + a bar chart.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$metrics = is_array($v['metrics'] ?? null) ? $v['metrics'] : ['cards' => [], 'chart' => ['labels' => [], 'values' => []]];
$cards   = is_array($metrics['cards'] ?? null) ? $metrics['cards'] : [];
$chart   = is_array($metrics['chart'] ?? null) ? $metrics['chart'] : ['labels' => [], 'values' => []];
?>
<h1>Dashboard</h1>
<p class="who">Platform overview</p>
<div class="cards">
    <?php foreach ($cards as $card): ?>
        <?= $view->partial('components/stat_card', ['label' => $card['label'] ?? '', 'value' => $card['value'] ?? 0]) ?>
    <?php endforeach; ?>
</div>
<?= $view->partial('components/chart', ['title' => 'Platform totals', 'labels' => $chart['labels'] ?? [], 'values' => $chart['values'] ?? []]) ?>
