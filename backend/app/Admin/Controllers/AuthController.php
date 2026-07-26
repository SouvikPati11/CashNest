<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AdminAuthService;
use App\Admin\View\AdminView;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Admin authentication controller (session login, 2FA step, logout).
 *
 * Pre-authentication, so it does not extend BaseAdminController. Renders the
 * standalone login screen and drives the AdminAuthService state machine.
 */
final class AuthController
{
    public function __construct(
        private AdminView $view,
        private AdminAuthService $auth
    ) {
    }

    public function showLogin(Request $request): Response
    {
        if ($this->auth->id() !== null) {
            return $this->redirect('/admin');
        }

        return $this->html($this->view->bare('auth/login', ['two_factor' => false, 'email' => '']));
    }

    public function login(Request $request): Response
    {
        $email    = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $code     = $request->input('code');
        $code     = is_string($code) && $code !== '' ? $code : null;

        $result = $this->auth->attempt($email, $password, $code, $request->ip());

        return match ($result['status']) {
            AdminAuthService::OK => $this->redirect('/admin'),
            AdminAuthService::TWO_FACTOR_REQUIRED => $this->html($this->view->bare('auth/login', [
                'two_factor' => true,
                'email'      => $email,
                'info'       => 'Enter the 6-digit code from your authenticator app.',
            ])),
            AdminAuthService::TWO_FACTOR_INVALID => $this->html($this->view->bare('auth/login', [
                'two_factor' => true,
                'email'      => $email,
                'error'      => 'The 2FA code is invalid.',
            ]), 422),
            default => $this->html($this->view->bare('auth/login', [
                'two_factor' => false,
                'email'      => $email,
                'error'      => 'Invalid email or password.',
            ]), 422),
        };
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout($request->ip());

        return $this->redirect('/admin/login');
    }

    private function html(string $body, int $status = 200): Response
    {
        return new Response($status, $body, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function redirect(string $to): Response
    {
        return new Response(302, '', ['Location' => $to]);
    }
}
