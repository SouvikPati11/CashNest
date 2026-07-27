<?php

declare(strict_types=1);

namespace App\Admin\View;

/**
 * Minimal PHP-template renderer for the admin panel.
 *
 * Templates live under `resources/admin/views` and receive the data array as
 * `$v` and the renderer itself as `$view` (for escaping and partials). No
 * `extract()` is used, keeping template variable access explicit and safe.
 * Output is captured via output buffering.
 */
final class ViewRenderer
{
    private string $viewsPath;

    public function __construct(?string $viewsPath = null)
    {
        $this->viewsPath = rtrim($viewsPath ?? dirname(__DIR__, 3) . '/resources/admin/views', '/');
    }

    /**
     * Render a template to a string.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $file = $this->viewsPath . '/' . trim($template, '/') . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException(sprintf('Admin view not found: %s', $template));
        }

        $capture = function () use ($file, $data): string {
            $view = $this;
            $v    = $data;
            ob_start();
            require $file;

            return (string) ob_get_clean();
        };

        return $capture();
    }

    /**
     * Render a partial (alias of render, for readability inside templates).
     *
     * @param array<string, mixed> $data
     */
    public function partial(string $template, array $data = []): string
    {
        return $this->render($template, $data);
    }

    /**
     * HTML-escape a value for safe output.
     */
    public function e(mixed $value): string
    {
        $string = is_scalar($value) ? (string) $value : '';

        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
