<?php

declare(strict_types=1);

namespace App\Admin\View;

use App\Admin\Security\CsrfGuard;
use App\Admin\Services\AdminAuthService;
use App\Admin\Services\RbacService;
use App\Admin\Support\SessionInterface;
use App\Models\Admin;
use Core\Config;

/**
 * Admin page composer.
 *
 * Wraps a page template in the shared layout, supplying the permission-filtered
 * sidebar navigation, the current admin, the CSRF token, and one-shot flash
 * messages. Keeps controllers free of view/layout wiring.
 */
final class AdminView
{
    private const FLASH_KEY = '_flash';

    public function __construct(
        private ViewRenderer $renderer,
        private Config $config,
        private CsrfGuard $csrf,
        private AdminAuthService $auth,
        private RbacService $rbac,
        private SessionInterface $session
    ) {
    }

    /**
     * Render a full admin page (layout + content).
     *
     * @param array<string, mixed> $data
     */
    public function page(string $template, array $data, string $activeKey): string
    {
        $content = $this->renderer->render('pages/' . $template, $data + ['csrf' => $this->csrf->token()]);

        return $this->renderer->render('layout', [
            'content' => $content,
            'nav'     => $this->navigation(),
            'active'  => $activeKey,
            'brand'   => (string) $this->config->get('admin.brand', 'CashNest Admin'),
            'admin'   => $this->auth->current(),
            'csrf'    => $this->csrf->token(),
            'flash'   => $this->takeFlash(),
        ]);
    }

    /**
     * Render a standalone page without the app chrome (e.g. login).
     *
     * @param array<string, mixed> $data
     */
    public function bare(string $template, array $data): string
    {
        return $this->renderer->render('pages/' . $template, $data + [
            'csrf'  => $this->csrf->token(),
            'brand' => (string) $this->config->get('admin.brand', 'CashNest Admin'),
            'flash' => $this->takeFlash(),
        ]);
    }

    /**
     * Store a one-shot flash message for the next request.
     */
    public function flash(string $type, string $message): void
    {
        $this->session->set(self::FLASH_KEY, ['type' => $type, 'message' => $message]);
    }

    /**
     * The sidebar navigation filtered to the current admin's permissions.
     *
     * @return array<int, array<string, mixed>>
     */
    private function navigation(): array
    {
        $nav = $this->config->get('admin.navigation', []);
        if (!is_array($nav)) {
            return [];
        }

        $current = $this->auth->current();
        $roleId  = $current !== null ? (int) Admin::fromRow($current)->roleId() : 0;

        $visible = [];
        foreach ($nav as $item) {
            if (!is_array($item)) {
                continue;
            }
            $permission = is_string($item['permission'] ?? null) ? $item['permission'] : '';
            if ($permission === '' || $this->rbac->can($roleId, $permission)) {
                $visible[] = $item;
            }
        }

        return $visible;
    }

    /**
     * @return array{type: string, message: string}|null
     */
    private function takeFlash(): ?array
    {
        $flash = $this->session->get(self::FLASH_KEY);
        $this->session->forget(self::FLASH_KEY);

        if (is_array($flash) && isset($flash['type'], $flash['message'])) {
            return ['type' => (string) $flash['type'], 'message' => (string) $flash['message']];
        }

        return null;
    }
}
