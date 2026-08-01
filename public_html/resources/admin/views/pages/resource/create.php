<?php
/**
 * Generic resource create form: one input per editable column.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$resource = is_array($v['resource'] ?? null) ? $v['resource'] : [];
$editable = is_array($resource['editable'] ?? null) ? $resource['editable'] : [];
$key      = (string) ($v['key'] ?? '');
$title    = (string) ($resource['title'] ?? 'Record');
?>
<h1>New <?= $view->e($title) ?></h1>
<div class="panel">
    <form method="post" action="/admin/r/<?= $view->e($key) ?>">
        <input type="hidden" name="_token" value="<?= $view->e($v['csrf'] ?? '') ?>">
        <?php foreach ($editable as $column): ?>
            <?php $label = ucwords(str_replace('_', ' ', (string) $column)); ?>
            <div class="field">
                <label for="f_<?= $view->e((string) $column) ?>"><?= $view->e($label) ?></label>
                <input id="f_<?= $view->e((string) $column) ?>" type="text" name="<?= $view->e((string) $column) ?>" value="">
            </div>
        <?php endforeach; ?>
        <button class="btn" type="submit">Create</button>
    </form>
</div>
<a class="btn tonal" href="/admin/r/<?= $view->e($key) ?>">Back</a>
