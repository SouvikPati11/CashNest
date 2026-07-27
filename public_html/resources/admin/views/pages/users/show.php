<?php
/**
 * User detail + status actions.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$user = is_array($v['user'] ?? null) ? $v['user'] : [];
$id   = (string) ($user['id'] ?? '');
?>
<h1>User #<?= $view->e($id) ?></h1>
<div class="panel">
    <table>
        <tbody>
        <?php foreach (['uuid', 'name', 'email', 'status', 'country_code', 'created_at'] as $field): ?>
            <tr>
                <th><?= $view->e(ucwords(str_replace('_', ' ', $field))) ?></th>
                <td><?= $view->e(is_scalar($user[$field] ?? null) ? (string) $user[$field] : '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="panel">
    <h2>Account status</h2>
    <form method="post" action="/admin/users/<?= $view->e($id) ?>/status">
        <input type="hidden" name="_token" value="<?= $view->e($v['csrf'] ?? '') ?>">
        <select name="status">
            <?php foreach (['active', 'suspended', 'banned'] as $status): ?>
                <option value="<?= $view->e($status) ?>" <?= ($user['status'] ?? '') === $status ? 'selected' : '' ?>>
                    <?= $view->e(ucfirst($status)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Update status</button>
    </form>
</div>
<a class="btn tonal" href="/admin/users">Back to users</a>
