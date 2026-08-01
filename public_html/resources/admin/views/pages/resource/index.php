<?php
/**
 * Generic resource management browser (list + search + pagination).
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$resource = is_array($v['resource'] ?? null) ? $v['resource'] : ['title' => 'Records', 'columns' => []];
$columns  = is_array($resource['columns'] ?? null) ? $resource['columns'] : [];
$editable  = is_array($resource['editable'] ?? null) ? $resource['editable'] : [];
$key       = (string) ($v['key'] ?? '');
$editBase  = $editable !== [] ? '/admin/r/' . $key : '';
$creatable = (bool) ($resource['creatable'] ?? false);
?>
<h1><?= $view->e($resource['title'] ?? 'Records') ?></h1>
<?php if ($creatable): ?>
    <p><a class="btn" href="/admin/r/<?= $view->e($key) ?>/new">+ New <?= $view->e($resource['title'] ?? 'Record') ?></a></p>
<?php endif; ?>
<?= $view->partial('components/filters', ['action' => '/admin/r/' . $key, 'search' => $v['search'] ?? '']) ?>
<?= $view->partial('components/data_table', ['columns' => $columns, 'rows' => $v['rows'] ?? [], 'edit_base' => $editBase]) ?>
<?= $view->partial('components/pagination', ['paginator' => $v['paginator'] ?? [], 'base' => '/admin/r/' . $key . '?q=' . urlencode((string) ($v['search'] ?? ''))]) ?>
