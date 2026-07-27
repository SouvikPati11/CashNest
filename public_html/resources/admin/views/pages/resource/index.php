<?php
/**
 * Generic resource management browser (list + search + pagination).
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$resource = is_array($v['resource'] ?? null) ? $v['resource'] : ['title' => 'Records', 'columns' => []];
$columns  = is_array($resource['columns'] ?? null) ? $resource['columns'] : [];
$key      = (string) ($v['key'] ?? '');
?>
<h1><?= $view->e($resource['title'] ?? 'Records') ?></h1>
<?= $view->partial('components/filters', ['action' => '/admin/r/' . $key, 'search' => $v['search'] ?? '']) ?>
<?= $view->partial('components/data_table', ['columns' => $columns, 'rows' => $v['rows'] ?? []]) ?>
<?= $view->partial('components/pagination', ['paginator' => $v['paginator'] ?? [], 'base' => '/admin/r/' . $key . '?q=' . urlencode((string) ($v['search'] ?? ''))]) ?>
