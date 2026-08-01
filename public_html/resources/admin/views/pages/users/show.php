<?php
/**
 * User detail: account info, wallet balance, and status actions.
 *
 * @var \App\Admin\View\ViewRenderer $view
 * @var array<string, mixed> $v
 */
$user   = is_array($v['user'] ?? null) ? $v['user'] : [];
$wallet = is_array($v['wallet'] ?? null) ? $v['wallet'] : [];
$id     = (string) ($user['id'] ?? '');

$fmt = static function ($value): string {
    return is_scalar($value) ? (string) $value : '—';
};
$statusChip = static function (string $status): string {
    return ['active' => 'ok', 'suspended' => 'warn', 'banned' => 'bad', 'deleted' => 'bad'][$status] ?? '';
};
?>
<h1>User #<?= $view->e($id) ?></h1>
<div class="panel">
    <h2>Account</h2>
    <table>
        <tbody>
        <?php
        $fields = [
            'uuid', 'name', 'email', 'phone', 'status', 'country_code', 'locale',
            'referral_code', 'referred_by', 'email_verified_at', 'last_login_at',
            'registration_ip', 'created_at',
        ];
        foreach ($fields as $field): ?>
            <tr>
                <th><?= $view->e(ucwords(str_replace('_', ' ', $field))) ?></th>
                <td>
                    <?php if ($field === 'status'): ?>
                        <span class="chip <?= $view->e($statusChip((string) ($user['status'] ?? ''))) ?>"><?= $view->e($fmt($user['status'] ?? null)) ?></span>
                    <?php else: ?>
                        <?= $view->e($fmt($user[$field] ?? null)) ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="panel">
    <h2>Wallet</h2>
    <?php if ($wallet === []): ?>
        <p>No wallet found for this user.</p>
    <?php else: ?>
        <table>
            <tbody>
            <?php
            $walletFields = [
                'coin_balance'          => 'Coin balance',
                'coin_reserved'         => 'Coin reserved',
                'lifetime_coins_earned' => 'Lifetime earned',
                'lifetime_coins_spent'  => 'Lifetime spent',
                'cash_balance'          => 'Cash balance',
                'cash_reserved'         => 'Cash reserved',
            ];
            foreach ($walletFields as $key => $label): ?>
                <tr>
                    <th><?= $view->e($label) ?></th>
                    <td><?= $view->e($fmt($wallet[$key] ?? null)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
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
