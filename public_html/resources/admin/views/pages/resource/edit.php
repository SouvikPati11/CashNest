<?php
/**
 * Generic resource editor: renders one input per editable column.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$resource = is_array($v['resource'] ?? null) ? $v['resource'] : [];
$row      = is_array($v['row'] ?? null) ? $v['row'] : [];
$editable = is_array($resource['editable'] ?? null) ? $resource['editable'] : [];
$key      = (string) ($v['key'] ?? '');
$id       = (string) ($row['id'] ?? '');
$title    = (string) ($resource['title'] ?? 'Record');
?>
<h1>Edit <?= $view->e($title) ?> #<?= $view->e($id) ?></h1>
<div class="panel">
    <form method="post" action="/admin/r/<?= $view->e($key) ?>/<?= $view->e($id) ?>">
        <input type="hidden" name="_token" value="<?= $view->e($v['csrf'] ?? '') ?>">
        <?php foreach ($editable as $column): ?>
            <?php
            $label = ucwords(str_replace('_', ' ', (string) $column));
            $value = is_scalar($row[$column] ?? null) ? (string) $row[$column] : '';
            ?>
            <div class="field">
                <label for="f_<?= $view->e((string) $column) ?>"><?= $view->e($label) ?></label>
                <input id="f_<?= $view->e((string) $column) ?>" type="text" name="<?= $view->e((string) $column) ?>" value="<?= $view->e($value) ?>">
            </div>
        <?php endforeach; ?>
        <button class="btn" type="submit">Save changes</button>
    </form>
</div>
<a class="btn tonal" href="/admin/r/<?= $view->e($key) ?>">Back</a>
