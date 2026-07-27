<?php

declare(strict_types=1);

use Core\Env;

/**
 * Filesystem / upload configuration.
 */
$split = static function (string $value): array {
    return array_values(array_filter(array_map('trim', explode(',', $value))));
};

return [
    'storage_path' => Env::get('STORAGE_PATH', 'storage'),
    'upload_path'  => Env::get('UPLOAD_PATH', 'storage/uploads'),
    'uploads'      => [
        'max_size'      => (int) Env::get('UPLOAD_MAX_SIZE', 5242880),
        'allowed_mimes' => $split((string) Env::get(
            'UPLOAD_ALLOWED_MIME',
            'image/jpeg,image/png,image/webp,application/pdf'
        )),
    ],
];
