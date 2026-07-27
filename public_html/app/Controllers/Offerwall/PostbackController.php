<?php

declare(strict_types=1);

namespace App\Controllers\Offerwall;

use App\Contracts\OfferwallPostbackServiceInterface as Postback;
use App\Controllers\BaseController;
use App\Helpers\ApiResponse;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Signed offerwall/CPA postback endpoints (API_SPECIFICATION.md §2.39, §2.42).
 *
 * Not JWT-authenticated: authenticity is established by HMAC signature + IP
 * allowlist inside the service. Duplicates are acknowledged (200) without
 * re-crediting; failures return the mapped error code.
 */
final class PostbackController extends BaseController
{
    public function __construct(private Postback $postback)
    {
    }

    public function offerwall(Request $request): Response
    {
        return $this->handle('offerwall', $request);
    }

    public function cpa(Request $request): Response
    {
        return $this->handle('cpa', $request);
    }

    /**
     * @param 'offerwall'|'cpa' $kind
     */
    private function handle(string $kind, Request $request): Response
    {
        // Providers may send data via query or body; merge (body wins).
        $payload = array_merge($request->allQuery(), $request->all());
        $slug    = (string) $request->routeParam('provider');

        $result = $this->postback->process($kind, $slug, $payload, $request->ip(), $request->method());

        return match ($result['result']) {
            Postback::RESULT_CREDITED   => ApiResponse::success(['status' => 'credited'], 'OK'),
            Postback::RESULT_DUPLICATE  => ApiResponse::success(['status' => 'duplicate'], 'Already processed.'),
            Postback::RESULT_INVALID_SIG => ApiResponse::error('Invalid signature.', 401, 'INVALID_SIGNATURE'),
            Postback::RESULT_IP_BLOCKED => ApiResponse::error('Source IP not allowed.', 403, 'IP_NOT_ALLOWED'),
            Postback::RESULT_USER_NOT_FOUND => ApiResponse::error('Attribution not found.', 404, 'NOT_FOUND'),
            default                     => ApiResponse::error('Postback rejected.', 422, 'VALIDATION_ERROR'),
        };
    }
}
