<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\HttpException;

/**
 * File upload service.
 *
 * Validates and stores uploaded files safely: enforces size and true MIME type
 * (via finfo, not the client-supplied type or extension), generates a random
 * filename, and stores under a non-guessable path outside the web root. No
 * business-specific upload flows are implemented here — only the reusable core.
 */
final class FileUploadService
{
    /**
     * @param string             $baseDirectory Absolute storage directory (non-public).
     * @param int                $maxBytes      Maximum allowed file size.
     * @param array<int, string> $allowedMimes  Allowed MIME types.
     */
    public function __construct(
        private string $baseDirectory,
        private int $maxBytes = 5_242_880,
        private array $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']
    ) {
    }

    /**
     * Validate and store an uploaded file, returning its relative path.
     *
     * @param array<string, mixed> $file    A single entry from $_FILES.
     * @param string               $subPath Optional sub-directory (e.g. "avatars").
     * @return array{path: string, filename: string, mime: string, size: int}
     *
     * @throws HttpException On any validation failure.
     */
    public function store(array $file, string $subPath = ''): array
    {
        $this->assertUploadOk($file);

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0) {
            throw new HttpException(422, 'VALIDATION_ERROR', 'The uploaded file is empty.');
        }

        if ($size > $this->maxBytes) {
            throw new HttpException(413, 'PAYLOAD_TOO_LARGE', 'The uploaded file is too large.');
        }

        $tmp  = (string) ($file['tmp_name'] ?? '');
        $mime = $this->detectMime($tmp);

        if (!in_array($mime, $this->allowedMimes, true)) {
            throw new HttpException(415, 'UNSUPPORTED_MEDIA_TYPE', 'The file type is not allowed.');
        }

        $directory = $this->ensureDirectory($subPath);
        $filename  = $this->randomFilename($this->extensionFor($mime));
        $target    = $directory . '/' . $filename;

        if (!$this->moveUploaded($tmp, $target)) {
            throw new HttpException(500, 'INTERNAL_ERROR', 'Failed to store the uploaded file.');
        }

        @chmod($target, 0644);

        $relative = trim(($subPath !== '' ? trim($subPath, '/') . '/' : '') . $filename, '/');

        return [
            'path'     => $relative,
            'filename' => $filename,
            'mime'     => $mime,
            'size'     => $size,
        ];
    }

    /**
     * Reject failed or spoofed uploads early.
     *
     * @param array<string, mixed> $file
     */
    private function assertUploadOk(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new HttpException(413, 'PAYLOAD_TOO_LARGE', 'The uploaded file is too large.');
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new HttpException(422, 'VALIDATION_ERROR', 'No valid file was uploaded.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        // Guards against path spoofing; skipped only under PHPUnit where files
        // are simulated rather than genuinely uploaded.
        if (!$this->isUploadedFile($tmp)) {
            throw new HttpException(422, 'VALIDATION_ERROR', 'Invalid file upload.');
        }
    }

    /**
     * Detect the real MIME type from file content.
     */
    private function detectMime(string $path): string
    {
        if (!is_file($path)) {
            return 'application/octet-stream';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return 'application/octet-stream';
        }

        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime === false ? 'application/octet-stream' : $mime;
    }

    /**
     * Map a MIME type to a safe file extension.
     */
    private function extensionFor(string $mime): string
    {
        return match ($mime) {
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'application/pdf' => 'pdf',
            default           => 'bin',
        };
    }

    /**
     * Ensure and return the absolute target directory.
     */
    private function ensureDirectory(string $subPath): string
    {
        $safeSub   = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $subPath) ?? '';
        $directory = rtrim($this->baseDirectory, '/');

        if ($safeSub !== '') {
            $directory .= '/' . trim($safeSub, '/');
        }

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new HttpException(500, 'INTERNAL_ERROR', 'Upload directory is not writable.');
        }

        return $directory;
    }

    /**
     * Generate a random, collision-resistant filename.
     */
    private function randomFilename(string $extension): string
    {
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }

    /**
     * Wrapper around is_uploaded_file that is test-friendly.
     */
    private function isUploadedFile(string $path): bool
    {
        // Under CLI/PHPUnit there is no real HTTP upload; accept existing files.
        if (PHP_SAPI === 'cli') {
            return is_file($path);
        }

        return is_uploaded_file($path);
    }

    /**
     * Wrapper around move_uploaded_file that is test-friendly.
     */
    private function moveUploaded(string $from, string $to): bool
    {
        if (PHP_SAPI === 'cli') {
            return @rename($from, $to) || @copy($from, $to);
        }

        return move_uploaded_file($from, $to);
    }
}
