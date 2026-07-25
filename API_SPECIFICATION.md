# CashNest — API Specification

> **Companion to (FINAL, unchanged):** `ARCHITECTURE.md` (SAD v1.1) · `DATABASE_DESIGN.md` (v1.0)
> **Role:** Lead API Architect
> **Status:** Production-ready specification — v1.0
> **Audience:** Backend developers (PHP MVC) and Flutter developers, working independently.
> **Transport:** HTTPS only · JSON request/response · UTF-8.

This document is the single contract between client and server. Every endpoint below is fully specified using **15 fields**: Module, URL, Method, Auth, Purpose, Request Headers, Request Body, Success Response, Error Responses, Validation Rules, Business Rules, Rate Limits, Idempotency, Security Notes, Database Tables Used.

> **Reading convention:** Global standards in **Part 1** (headers, response/error envelope, error codes, pagination, filtering, sorting) apply to **every** endpoint. Per-endpoint sections list only the **deltas** (endpoint-specific headers, error codes, etc.) on top of the standards. Where an endpoint says "Standard headers", use the set in §1.4.

---

# PART 1 — GLOBAL STANDARDS

## 1.1 API Versioning Strategy

- **Scheme:** URI path versioning. All endpoints are prefixed with `/v1`.
  - **Base URL (app/public):** `https://api.cashnest.app/v1`
  - **Admin API base:** `https://admin.cashnest.app/api` (server-rendered admin also uses session endpoints; see Part 3).
  - **Postbacks (server-to-server):** `https://api.cashnest.app/v1/postback/...`
- **Version lifecycle:** A new major version (`/v2`) is introduced only for breaking changes. Non-breaking additions (new optional fields, new endpoints) ship within `/v1`.
- **Deprecation:** Deprecated endpoints return a `Deprecation: true` and `Sunset: <RFC1123 date>` header for at least 90 days before removal.
- **Client version signaling:** clients send `X-App-Version` and `X-Platform` headers on every request (used for force-update gating, remote config targeting, and analytics).

## 1.2 Authentication Model

Three authentication contexts exist (matching the SAD):

| Auth Level | Mechanism | Used by |
|------------|-----------|---------|
| **Public** | None | Login, register, config, CMS, version, theme, banners |
| **User JWT** | `Authorization: Bearer <access_token>` | All end-user app endpoints |
| **Admin Session** | Session cookie + `X-CSRF-Token` | Admin panel endpoints |
| **Signed (postback)** | HMAC signature + IP allowlist | Offerwall/CPA/ad SSV callbacks |

- **Access token (JWT):** short-lived (default **30 min**), stateless, carries `sub` (user uuid), `jti`, `iat`, `exp`, `scope`.
- **Refresh token:** long-lived (default **30 days**), opaque, rotated on use, stored **hashed** server-side (`user_sessions`). Revocable.
- **Admin session:** server-side session (not JWT), CSRF-protected, optional 2FA, IP allowlist per §6.3 of the SAD.

## 1.3 Authentication Flow (overview)

```
1. Client authenticates (Google ID token OR email+password).
2. Server verifies → issues { access_token (JWT, 30m), refresh_token (30d) }.
3. Client stores tokens in secure storage; sends access_token as Bearer on each call.
4. On 401 TOKEN_EXPIRED → client calls POST /auth/refresh with refresh_token.
5. Server rotates refresh_token, returns a new pair.
6. On logout → POST /auth/logout revokes the current session (refresh token).
```
(Full sequence diagram in Part 3.)

## 1.4 Standard Request Headers

| Header | Required | Applies to | Description |
|--------|----------|------------|-------------|
| `Content-Type: application/json` | Yes (for bodies) | All with body | Request payload type. |
| `Accept: application/json` | Recommended | All | Response type. |
| `Authorization: Bearer <token>` | Yes | User JWT endpoints | Access token. |
| `X-CSRF-Token` | Yes | Admin mutating endpoints | CSRF protection. |
| `X-App-Version` | Yes | App endpoints | e.g., `1.4.0`. |
| `X-Platform` | Yes | App endpoints | `android` \| `ios` \| `web`. |
| `X-Device-Id` | Recommended | App endpoints | Stable device UUID (fraud/attribution). |
| `Accept-Language` | Optional | All | Locale (e.g., `en`, `hi`); drives CMS/notification locale. |
| `X-Idempotency-Key` | Conditional | Money-mutating POSTs | Client-generated UUID for safe retries (see §1.11). |
| `X-Request-Id` | Optional | All | Client correlation id; echoed back. |

## 1.5 Standard Response Format (envelope)

**Every** response — success or error — uses this envelope:

```json
{
  "status": "success",
  "message": "Human-readable summary",
  "data": { },
  "meta": null,
  "errors": null,
  "request_id": "b1c2d3e4-....",
  "timestamp": "2026-07-25T10:15:30Z"
}
```

| Field | Type | Description |
|-------|------|-------------|
| `status` | string | `success` or `error`. |
| `message` | string | Human-readable message (safe to display). |
| `data` | object \| array \| null | Payload on success; `null` on error. |
| `meta` | object \| null | Pagination/rate/context metadata (see §1.8). |
| `errors` | array \| null | Present only on errors (see §1.6). |
| `request_id` | string (uuid) | Server correlation id (also in logs). |
| `timestamp` | string (ISO-8601 UTC) | Server response time. |

## 1.6 Standard Error Format

```json
{
  "status": "error",
  "message": "Validation failed.",
  "data": null,
  "meta": null,
  "errors": [
    { "code": "VALIDATION_ERROR", "field": "email", "message": "Email is invalid." }
  ],
  "request_id": "b1c2d3e4-....",
  "timestamp": "2026-07-25T10:15:30Z"
}
```

- `errors` is always an **array** of `{ code, field, message }`.
- `field` is `null` for non-field errors.
- Multiple validation failures return multiple array entries in one response.

## 1.7 Standard Error Codes (reused project-wide)

| Code | HTTP | Meaning |
|------|------|---------|
| `VALIDATION_ERROR` | 422 | One or more request fields failed validation. |
| `AUTH_REQUIRED` | 401 | Missing credentials on a protected endpoint. |
| `INVALID_TOKEN` | 401 | Malformed/invalid access token. |
| `TOKEN_EXPIRED` | 401 | Access token expired → client should refresh. |
| `INVALID_REFRESH_TOKEN` | 401 | Refresh token invalid/expired/revoked → re-login. |
| `INVALID_CREDENTIALS` | 401 | Wrong email/password or bad Google token. |
| `EMAIL_NOT_VERIFIED` | 403 | Action requires a verified email. |
| `EMAIL_EXISTS` | 409 | Email already registered. |
| `FORBIDDEN` | 403 | Authenticated but not allowed (RBAC / ownership). |
| `NOT_FOUND` | 404 | Resource does not exist or not owned by caller. |
| `RESOURCE_CONFLICT` | 409 | State conflict (e.g., already processed). |
| `DUPLICATE_REQUEST` | 409 | Idempotency key already used with different payload. |
| `ALREADY_CLAIMED` | 409 | Reward/card/check-in already claimed. |
| `EXPIRED` | 410 | Resource expired (e.g., scratch card). |
| `LIMIT_REACHED` | 429 | Per-user/day limit reached (spin, ads, tasks). |
| `RATE_LIMITED` | 429 | Too many requests (throttle). |
| `INSUFFICIENT_BALANCE` | 422 | Not enough coins for the operation. |
| `MIN_WITHDRAW_NOT_MET` | 422 | Below minimum withdrawal threshold. |
| `KYC_REQUIRED` | 403 | KYC must be approved before this action. |
| `FRAUD_HOLD` | 403 | Account/withdrawals held by fraud engine. |
| `ACCOUNT_SUSPENDED` | 403 | Account suspended/banned. |
| `MAINTENANCE_MODE` | 503 | App in maintenance; endpoint gated. |
| `FORCE_UPDATE_REQUIRED` | 426 | Client below minimum supported version. |
| `INVALID_SIGNATURE` | 401 | Postback signature verification failed. |
| `IP_NOT_ALLOWED` | 403 | Postback source IP not on allowlist. |
| `UNSUPPORTED_MEDIA_TYPE` | 415 | Body/upload type not accepted. |
| `PAYLOAD_TOO_LARGE` | 413 | Upload exceeds size limit. |
| `INTERNAL_ERROR` | 500 | Unexpected server error. |
| `SERVICE_UNAVAILABLE` | 503 | Upstream/provider/gateway unavailable. |

**Global errors that may be returned by ANY endpoint** (not repeated per endpoint unless behavior differs): `INTERNAL_ERROR (500)`, `RATE_LIMITED (429)`, `MAINTENANCE_MODE (503)`, `SERVICE_UNAVAILABLE (503)`. **Any User-JWT endpoint** may additionally return `AUTH_REQUIRED (401)`, `INVALID_TOKEN (401)`, `TOKEN_EXPIRED (401)`, `ACCOUNT_SUSPENDED (403)`, and `FORCE_UPDATE_REQUIRED (426)`.

## 1.8 Pagination Standard

- **Style:** cursor-based (preferred) with offset fallback. Query params:
  - `limit` (int, default `20`, max `100`).
  - `cursor` (opaque string) — for forward pagination.
  - `page` (int, ≥1) — offset fallback where cursor is impractical.
- **Response `meta.pagination`:**

```json
"meta": {
  "pagination": {
    "limit": 20,
    "next_cursor": "eyJpZCI6MTIzfQ==",
    "prev_cursor": null,
    "has_more": true,
    "total": 342
  }
}
```
- `total` is best-effort and may be omitted for very large tables (e.g., `wallet_transactions`) to avoid expensive counts.

## 1.9 Filtering Standard

- Filters are query params prefixed with `filter[...]`:
  - Example: `?filter[type]=offerwall&filter[status]=credited&filter[date_from]=2026-07-01&filter[date_to]=2026-07-25`
- Date ranges use `date_from` / `date_to` (ISO-8601, UTC, inclusive).
- Multiple values: comma-separated (`filter[status]=pending,approved`).
- Unknown filter keys are ignored (forward-compatible) but logged.

## 1.10 Sorting Standard

- `sort` query param: comma-separated fields; `-` prefix = descending.
  - Example: `?sort=-created_at,amount`
- Each endpoint declares its **allowed sort fields**; others → `VALIDATION_ERROR`.
- Default sort per endpoint is documented; typically `-created_at`.

## 1.11 Idempotency Standard

- **Applies to:** every **money-mutating** or **externally-triggered** POST (claims, spins, task completion, offer clicks, withdrawals, rewarded-ad verification, postbacks).
- **Client-initiated:** client sends `X-Idempotency-Key` (UUID v4). Server stores the first result keyed by `(user_id, endpoint, key)`; replays with the **same** key return the original result with `200`/original status. Same key + **different** body → `DUPLICATE_REQUEST (409)`.
- **Server-side ledger guard:** all credits/debits also carry a deterministic `reference_id` unique in `wallet_transactions` (per `DATABASE_DESIGN.md` §N.1), guaranteeing no double-credit even without a client key.
- **Postbacks:** idempotent by provider transaction id (`offer_conversions.uq_ocv_provider_txn`) / SSV token (`ad_network_events.uq_ane_ssv_token`).

## 1.12 Rate Limiting Standard

- Enforced per **user** (JWT) and per **IP** (public). Response headers on every rate-limited-eligible endpoint:
  - `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset` (epoch seconds).
- Exceeding a limit → `429 RATE_LIMITED` with `Retry-After` header.
- Default tiers (overridable per endpoint below):
  - **Auth endpoints:** 10 req / 5 min / IP.
  - **Reward/earn endpoints:** 30 req / min / user.
  - **Read endpoints:** 120 req / min / user.
  - **Postbacks:** provider-scoped, high ceiling, IP-gated.

## 1.13 Common Field Conventions

- All IDs exposed to clients are **UUIDs/opaque strings**, never numeric PKs (per DB design).
- All monetary **coins** are integers; **cash** amounts are decimal strings with 4 dp (e.g., `"12.5000"`) to avoid float loss.
- All timestamps are ISO-8601 UTC (`2026-07-25T10:15:30Z`).
- Enumerations are lowercase snake_case strings matching the DB enums.

---

# PART 2 — ENDPOINT SPECIFICATIONS

---

## MODULE: Authentication

Base: `/v1/auth` · DB tables: `users`, `user_auth_providers`, `user_sessions`, `user_devices`, `user_settings`, `wallets`, `referrals`.

### 2.1 POST /v1/auth/google

1. **Module** — Authentication
2. **URL** — `/v1/auth/google`
3. **Method** — POST
4. **Auth** — Public
5. **Purpose** — Login or register a user using a Google ID token; issues app tokens.
6. **Request Headers** — Standard headers (no `Authorization`).
7. **Request Body**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `id_token` | string | Yes | Non-empty; valid Google-issued OIDC ID token. |
| `referral_code` | string | No | 6–12 alphanumeric; must exist if provided. |
| `device` | object | No | Device registration payload (see /auth/device body). |

8. **Success Response** — `200 OK`

```json
{
  "status": "success",
  "message": "Authenticated successfully.",
  "data": {
    "user": { "uuid": "u_9f...", "name": "Asha", "email": "asha@example.com", "avatar_url": null, "referral_code": "ASHA12", "status": "active", "is_new": false },
    "tokens": { "access_token": "eyJ...", "token_type": "Bearer", "expires_in": 1800, "refresh_token": "rt_8b..." }
  },
  "meta": null, "errors": null,
  "request_id": "…", "timestamp": "2026-07-25T10:15:30Z"
}
```

9. **Error Responses** — `INVALID_CREDENTIALS (401)` (token verification failed), `VALIDATION_ERROR (422)`, `ACCOUNT_SUSPENDED (403)`, `NOT_FOUND (404)` (referral_code invalid → returned as VALIDATION_ERROR on `referral_code`).
10. **Validation Rules** — Google ID token signature, `aud`, `iss`, and `exp` verified **server-side**. Email extracted from verified token only.
11. **Business Rules** — First login auto-creates `users` + `wallets` + `user_settings` (defaults). If `referral_code` present and user is new → create `referrals` (status `pending`). `is_new` indicates registration vs login. Links Google identity in `user_auth_providers`.
12. **Rate Limits** — 10 / 5 min / IP.
13. **Idempotency** — Not required (login is naturally idempotent per identity); repeated calls return fresh token pairs.
14. **Security Notes** — Never trust client-provided email/uid; only values from the verified token. New session recorded in `user_sessions`; refresh token stored hashed.
15. **DB Tables** — `users`, `user_auth_providers`, `user_sessions`, `wallets`, `user_settings`, `referrals`, `user_devices`.

### 2.2 POST /v1/auth/email/register

1–4. Authentication · `/v1/auth/email/register` · POST · Public
5. **Purpose** — Register a new account with email + password; sends verification.
6. **Headers** — Standard.
7. **Request Body**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `name` | string | Yes | 2–120 chars. |
| `email` | string | Yes | RFC email, ≤255, unique, lowercased. |
| `password` | string | Yes | 8–72 chars, ≥1 letter + ≥1 digit. |
| `referral_code` | string | No | 6–12 alphanumeric; must exist. |

8. **Success** — `201 Created` → `{ data: { user: {...}, verification_required: true } }` (no tokens until verified, unless policy allows unverified login — see business rules).
9. **Errors** — `EMAIL_EXISTS (409)`, `VALIDATION_ERROR (422)`.
10. **Validation** — Password strength enforced server-side; email uniqueness checked in `user_auth_providers`/`users`.
11. **Business Rules** — Creates `users` (email_verified_at NULL) + `wallets` + `user_settings`; stores argon2/bcrypt hash in `user_auth_providers`. Sends OTP/verification link. Referral pending as in 2.1.
12. **Rate Limits** — 5 / 5 min / IP.
13. **Idempotency** — Not required; duplicate email → `EMAIL_EXISTS`.
14. **Security** — Password never logged/returned; stored only as hash. Verification token single-use, expiring.
15. **DB Tables** — `users`, `user_auth_providers`, `wallets`, `user_settings`, `referrals`.

### 2.3 POST /v1/auth/email/login

1–4. Authentication · `/v1/auth/email/login` · POST · Public
5. **Purpose** — Authenticate with email + password; issues tokens.
7. **Body** — `email` (string, req), `password` (string, req), optional `device`.
8. **Success** — `200 OK` → same token shape as 2.1.
9. **Errors** — `INVALID_CREDENTIALS (401)`, `EMAIL_NOT_VERIFIED (403)` (if policy requires verification), `ACCOUNT_SUSPENDED (403)`, `VALIDATION_ERROR (422)`.
10. **Validation** — Constant-time hash comparison.
11. **Business Rules** — Updates `last_login_at`; creates `user_sessions`; registers/updates device if `device` present.
12. **Rate Limits** — 10 / 5 min / IP + progressive lockout after repeated failures per email.
13. **Idempotency** — N/A.
14. **Security** — Uniform error for wrong email vs wrong password (`INVALID_CREDENTIALS`) to prevent enumeration.
15. **DB Tables** — `users`, `user_auth_providers`, `user_sessions`, `user_devices`.

### 2.4 POST /v1/auth/email/verify

1–4. Authentication · `/v1/auth/email/verify` · POST · Public
5. **Purpose** — Verify email via OTP or token.
7. **Body** — `email` (req), and one of `otp` (6 digits) or `token` (string).
8. **Success** — `200 OK` → `{ data: { verified: true, tokens: {...} } }` (tokens issued on success).
9. **Errors** — `VALIDATION_ERROR (422)`, `INVALID_TOKEN (401)` (bad/expired OTP → code `INVALID_TOKEN`), `NOT_FOUND (404)`.
10. **Validation** — OTP/token single-use, expiry ≤15 min, max 5 attempts.
11. **Business Rules** — Sets `users.email_verified_at`; issues session.
12. **Rate Limits** — 10 / 15 min / IP.
13. **Idempotency** — Already-verified returns `200` idempotently.
14. **Security** — Attempts throttled; token invalidated after success.
15. **DB Tables** — `users`, `user_sessions`.

### 2.5 POST /v1/auth/password/forgot

1–4. Authentication · `/v1/auth/password/forgot` · POST · Public
5. **Purpose** — Request a password-reset link/OTP.
7. **Body** — `email` (req).
8. **Success** — `200 OK` → `{ data: { sent: true } }` (always generic).
9. **Errors** — `VALIDATION_ERROR (422)`, `RATE_LIMITED (429)`.
10. **Validation** — Email format.
11. **Business Rules** — Always returns success regardless of email existence (no enumeration). Generates single-use reset token.
12. **Rate Limits** — 5 / 15 min / IP + per email.
13. **Idempotency** — Safe to retry; only latest token valid.
14. **Security** — Do not reveal whether email exists.
15. **DB Tables** — `users`, `user_auth_providers`.

### 2.6 POST /v1/auth/password/reset

1–4. Authentication · `/v1/auth/password/reset` · POST · Public
7. **Body** — `email` (req), `token`/`otp` (req), `password` (req, same policy as register).
8. **Success** — `200 OK` → `{ data: { reset: true } }`.
9. **Errors** — `INVALID_TOKEN (401)`, `VALIDATION_ERROR (422)`.
10. **Validation** — Token single-use + unexpired; password strength.
11. **Business Rules** — Updates hash; **revokes all existing sessions** (`user_sessions`) → forces re-login everywhere.
12. **Rate Limits** — 5 / 15 min / IP.
14. **Security** — All refresh tokens invalidated on reset.
15. **DB Tables** — `user_auth_providers`, `user_sessions`.

### 2.7 POST /v1/auth/refresh

1–4. Authentication · `/v1/auth/refresh` · POST · Refresh token (in body)
5. **Purpose** — Exchange a valid refresh token for a new token pair (rotation).
7. **Body** — `refresh_token` (string, req).
8. **Success** — `200 OK` → `{ data: { tokens: {...} } }`.
9. **Errors** — `INVALID_REFRESH_TOKEN (401)`.
10. **Validation** — Hash lookup in `user_sessions`, not expired, not revoked.
11. **Business Rules** — **Rotation**: old refresh token revoked, new one issued; reuse of a revoked token → treat as theft: revoke the whole session chain.
12. **Rate Limits** — 60 / hour / user.
13. **Idempotency** — Not idempotent (rotation); replays fail with `INVALID_REFRESH_TOKEN`.
14. **Security** — Only the hash is stored; rotation + reuse-detection.
15. **DB Tables** — `user_sessions`.

### 2.8 POST /v1/auth/logout

1–4. Authentication · `/v1/auth/logout` · POST · User JWT
7. **Body** — optional `refresh_token` (to revoke a specific session); default revokes current.
8. **Success** — `200 OK` → `{ data: { logged_out: true } }`.
9. **Errors** — Standard JWT errors.
11. **Business Rules** — Marks `user_sessions.revoked_at`. Optionally clears device push token.
12. **Rate Limits** — 30 / min / user.
15. **DB Tables** — `user_sessions`, `user_devices`.

### 2.9 POST /v1/auth/device

1–4. Authentication · `/v1/auth/device` · POST · User JWT
5. **Purpose** — Register/update the device and its FCM token.
7. **Request Body**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `device_uuid` | string | Yes | ≤191 chars, stable per install. |
| `fcm_token` | string | No | ≤255. |
| `platform` | enum | Yes | `android`\|`ios`\|`web`. |
| `device_model` | string | No | ≤120. |
| `os_version` | string | No | ≤40. |
| `app_version` | string | No | ≤20. |
| `fingerprint_hash` | string | No | ≤191. |
| `is_emulator` | bool | No | — |
| `is_rooted` | bool | No | — |

8. **Success** — `200 OK` → `{ data: { device_id: "d_...", registered: true } }`.
9. **Errors** — `VALIDATION_ERROR (422)`.
11. **Business Rules** — Upsert on `(user_id, device_uuid)`. Reassigns an FCM token from a previous owner. Emulator/root/shared-fingerprint may create a `fraud_flags` entry.
12. **Rate Limits** — 20 / min / user.
13. **Idempotency** — Upsert = naturally idempotent.
14. **Security** — Fingerprint used for multi-account detection.
15. **DB Tables** — `user_devices`, `fraud_flags`.

---

## MODULE: Profile

Base: `/v1/profile` · Auth: User JWT · DB: `users`, `wallets`, `user_kyc`.

### 2.10 GET /v1/profile

5. **Purpose** — Fetch current user's profile + wallet summary.
6. **Headers** — Standard + `Authorization`.
7. **Body** — none.
8. **Success** — `200 OK`

```json
{ "status": "success", "message": "OK",
  "data": { "uuid": "u_9f", "name": "Asha", "email": "asha@example.com", "avatar_url": null,
    "referral_code": "ASHA12", "country_code": "IN", "status": "active",
    "wallet": { "coin_balance": 4200, "coin_reserved": 0, "cash_balance": "4.2000", "currency": "INR" },
    "kyc_status": "approved", "created_at": "2026-01-02T09:00:00Z" },
  "meta": null, "errors": null, "request_id": "…", "timestamp": "…" }
```

9. **Errors** — Standard JWT errors.
12. **Rate Limits** — 120 / min / user.
14. **Security** — Only the caller's own record.
15. **DB Tables** — `users`, `wallets`, `user_kyc`.

### 2.11 PUT /v1/profile

5. **Purpose** — Update profile fields.
7. **Body** — `name` (opt, 2–120), `country_code` (opt, ISO-2), `locale` (opt).
8. **Success** — `200 OK` → updated profile object.
9. **Errors** — `VALIDATION_ERROR (422)`.
10. **Validation** — Whitelist editable fields only; email is **not** editable here.
12. **Rate Limits** — 30 / min / user.
15. **DB Tables** — `users`.

### 2.12 POST /v1/profile/avatar

5. **Purpose** — Upload a profile avatar image.
6. **Headers** — `Content-Type: multipart/form-data` + `Authorization`.
7. **Body** — `avatar` (file, req): JPEG/PNG/WebP, ≤2 MB, ≤2048×2048.
8. **Success** — `200 OK` → `{ data: { avatar_url: "https://cdn/..." } }`.
9. **Errors** — `UNSUPPORTED_MEDIA_TYPE (415)`, `PAYLOAD_TOO_LARGE (413)`, `VALIDATION_ERROR (422)`.
10. **Validation** — MIME sniffing (not just extension); dimension/size caps.
12. **Rate Limits** — 10 / hour / user.
14. **Security** — Re-encode/strip EXIF; store outside web root or via safe naming; serve via CDN.
15. **DB Tables** — `users`.

### 2.13 GET /v1/profile/kyc

5. **Purpose** — Get KYC status/details (masked).
8. **Success** — `200 OK` → `{ data: { status: "pending|submitted|approved|rejected", document_type, masked_number: "XXXX1234", rejection_reason } }`.
15. **DB Tables** — `user_kyc`.

### 2.14 POST /v1/profile/kyc

5. **Purpose** — Submit KYC details/documents.
6. **Headers** — `multipart/form-data`.
7. **Body** — `full_name` (req), `document_type` (enum req), `document_number` (req), `document_file` (file req: JPEG/PNG/PDF ≤5 MB).
8. **Success** — `202 Accepted` → `{ data: { status: "submitted" } }`.
9. **Errors** — `VALIDATION_ERROR (422)`, `RESOURCE_CONFLICT (409)` (already approved), `PAYLOAD_TOO_LARGE (413)`.
11. **Business Rules** — One active KYC per user; status → submitted; enters admin review queue.
12. **Rate Limits** — 5 / day / user.
14. **Security** — `document_number` **encrypted at rest**; never returned in full; access audited.
15. **DB Tables** — `user_kyc`.

### 2.15 DELETE /v1/profile

5. **Purpose** — Request account deletion (GDPR).
7. **Body** — optional `reason`.
8. **Success** — `202 Accepted` → `{ data: { deletion: "scheduled", effective_at: "..." } }`.
9. **Errors** — `RESOURCE_CONFLICT (409)` (pending withdrawals block deletion).
11. **Business Rules** — Cannot delete with pending withdrawals or unresolved fraud holds. Anonymizes PII, sets `status=deleted`, `deleted_at`; **ledger/audit retained** (per DB §N.3). Revokes all sessions.
12. **Rate Limits** — 3 / day / user.
15. **DB Tables** — `users`, `user_sessions`, `withdraw_requests` (check).

---

## MODULE: Wallet

Base: `/v1/wallet` · Auth: User JWT · DB: `wallets`, `wallet_transactions`, `currency_settings`.

### 2.16 GET /v1/wallet

5. **Purpose** — Current coin + cash balance.
8. **Success** — `200 OK` → `{ data: { coin_balance: 4200, coin_reserved: 0, available: 4200, cash_balance: "4.2000", currency: "INR", lifetime_earned: 12000, lifetime_spent: 7800 } }`.
12. **Rate Limits** — 120 / min / user.
14. **Security** — Read-only; server-computed from `wallets` (ledger-backed).
15. **DB Tables** — `wallets`, `currency_settings`.

### 2.17 GET /v1/wallet/conversion

5. **Purpose** — Current coin→cash conversion rate + thresholds.
8. **Success** — `200 OK` → `{ data: { coin_to_cash_rate: "0.00100000", currency: "INR", min_withdraw_coins: 5000, max_withdraw_coins: 100000 } }`.
4. **Auth** — User JWT.
15. **DB Tables** — `currency_settings`.

---

## MODULE: Wallet Transactions

### 2.18 GET /v1/wallet/transactions

1–4. Wallet Transactions · `/v1/wallet/transactions` · GET · User JWT
5. **Purpose** — Paginated ledger history for the user.
6. **Headers** — Standard + `Authorization`.
7. **Query** — Pagination (§1.8); Filters: `filter[type]`, `filter[direction]` (`credit|debit`), `filter[date_from]`, `filter[date_to]`; Sort: `-created_at` (default), `amount`.
8. **Success** — `200 OK`

```json
{ "status":"success","message":"OK",
  "data":[
    { "uuid":"wt_a1","direction":"credit","amount":100,"balance_after":4200,
      "type":"offerwall","source_module":"offerwall","description":"AdGate offer",
      "created_at":"2026-07-24T18:00:00Z" }
  ],
  "meta": { "pagination": { "limit":20, "next_cursor":"…", "has_more":true } },
  "errors":null,"request_id":"…","timestamp":"…" }
```

9. **Errors** — `VALIDATION_ERROR (422)` (bad filter/sort), standard JWT errors.
10. **Validation** — Allowed filter/sort fields only; date range ≤ 1 year per query.
11. **Business Rules** — Read-only; returns only the caller's rows. `total` omitted (high-volume table).
12. **Rate Limits** — 120 / min / user.
14. **Security** — Strict user scoping; no other users' rows.
15. **DB Tables** — `wallet_transactions`.

### 2.19 GET /v1/wallet/transactions/{uuid}

5. **Purpose** — Single transaction detail.
7. **Path** — `uuid` (transaction uuid).
8. **Success** — `200 OK` → full transaction object incl. `metadata`, `related_transaction`.
9. **Errors** — `NOT_FOUND (404)` (missing or not owned).
14. **Security** — Ownership enforced.
15. **DB Tables** — `wallet_transactions`.

---

## MODULE: Daily Check-in

Base: `/v1/checkin` · Auth: User JWT · DB: `daily_checkins`, `checkin_rewards_config`, `wallet_transactions`, `wallets`.

### 2.20 GET /v1/checkin/status

5. **Purpose** — Current streak + whether today is claimable.
8. **Success** — `200 OK` → `{ data: { can_claim_today: true, current_streak: 3, next_reward_coins: 30, last_checkin_date: "2026-07-24" } }`.
12. **Rate Limits** — 120 / min / user.
15. **DB Tables** — `daily_checkins`, `checkin_rewards_config`.

### 2.21 GET /v1/checkin/calendar

5. **Purpose** — Reward ladder / calendar view.
8. **Success** — `200 OK` → `{ data: { ladder: [ { day:1, coins:10, is_milestone:false }, … ], current_streak:3 } }`.
15. **DB Tables** — `checkin_rewards_config`, `daily_checkins`.

### 2.22 POST /v1/checkin/claim ⭐

1–4. Daily Check-in · `/v1/checkin/claim` · POST · User JWT
5. **Purpose** — Claim today's check-in reward (credits coins).
6. **Headers** — Standard + `Authorization` + `X-Idempotency-Key` (recommended).
7. **Body** — none.
8. **Success** — `200 OK`

```json
{ "status":"success","message":"Check-in claimed.",
  "data":{ "coins_awarded":30, "streak_day":3, "new_balance":4230,
           "transaction_uuid":"wt_x9" },
  "meta":null,"errors":null,"request_id":"…","timestamp":"…" }
```

9. **Errors** — `ALREADY_CLAIMED (409)` (claimed today), `ACCOUNT_SUSPENDED (403)`, `FRAUD_HOLD (403)`.
10. **Validation** — Server determines "today" in UTC.
11. **Business Rules** — One claim per UTC day (`daily_checkins.uq_dc_user_date`). Streak increments if yesterday claimed, else resets to 1. Reward from `checkin_rewards_config` for the streak day. Credits via a single `wallet_transactions` row (`type=checkin`, `reference_id=checkin:<user>:<date>`) inside a locked wallet transaction.
12. **Rate Limits** — 30 / min / user.
13. **Idempotency** — Ledger `reference_id` guarantees single credit/day even on retry; client key returns original result.
14. **Security** — Reward computed server-side; client cannot specify amount.
15. **DB Tables** — `daily_checkins`, `checkin_rewards_config`, `wallets`, `wallet_transactions`.

---

## MODULE: Scratch Card

Base: `/v1/scratch` · Auth: User JWT · DB: `scratch_cards`, `scratch_card_config`, `wallets`, `wallet_transactions`.

### 2.23 GET /v1/scratch/available

5. **Purpose** — List available/unrevealed cards.
8. **Success** — `200 OK` → `{ data: [ { uuid:"sc_1", status:"issued", source:"daily", expires_at:"…" } ] }`.
15. **DB Tables** — `scratch_cards`.

### 2.24 POST /v1/scratch/{uuid}/reveal ⭐

5. **Purpose** — Reveal a card; server decides the reward.
6. **Headers** — + `X-Idempotency-Key`.
7. **Path** — `uuid`.
8. **Success** — `200 OK` → `{ data: { uuid, status:"revealed", reward_coins:50 } }`.
9. **Errors** — `NOT_FOUND (404)`, `RESOURCE_CONFLICT (409)` (already revealed), `EXPIRED (410)`.
11. **Business Rules** — Reward chosen **at reveal** via weighted random from `scratch_card_config` (respecting per-day prize caps). Status issued→revealed. Does **not** credit yet (claim does).
12. **Rate Limits** — 30 / min / user.
13. **Idempotency** — Re-reveal returns the same reward (state already `revealed`).
14. **Security** — Odds/config never exposed; reward server-side.
15. **DB Tables** — `scratch_cards`, `scratch_card_config`.

### 2.25 POST /v1/scratch/{uuid}/claim ⭐

5. **Purpose** — Claim the revealed reward (credits coins).
8. **Success** — `200 OK` → `{ data: { coins_awarded:50, new_balance:4280, transaction_uuid:"wt_..." } }`.
9. **Errors** — `NOT_FOUND (404)`, `RESOURCE_CONFLICT (409)` (not revealed / already claimed), `EXPIRED (410)`.
11. **Business Rules** — Requires status `revealed`; transitions to `claimed`; credits one `wallet_transactions` row (`type=scratch`, `reference_id=scratch:<card_uuid>`).
13. **Idempotency** — Ledger reference guards single credit.
15. **DB Tables** — `scratch_cards`, `wallets`, `wallet_transactions`.

### 2.26 GET /v1/scratch/history

5. **Purpose** — Past scratch results (paginated).
7. **Query** — Pagination; `filter[status]`.
8. **Success** — `200 OK` → list of cards.
15. **DB Tables** — `scratch_cards`.

---

## MODULE: Spin Wheel

Base: `/v1/spin` · Auth: User JWT · DB: `spin_wheel_segments`, `spin_history`, `wallets`, `wallet_transactions`.

### 2.27 GET /v1/spin/status

5. **Purpose** — Spins remaining today + wheel segments (display).
8. **Success** — `200 OK` → `{ data: { spins_remaining:2, daily_limit:3, segments:[ { id:"seg_1", label:"50", color_hex:"#FFCC00", position:0 } ] } }`.
14. **Security** — Weights/odds **not** exposed (only display fields).
15. **DB Tables** — `spin_wheel_segments`, `spin_history`.

### 2.28 POST /v1/spin ⭐

5. **Purpose** — Perform a spin; server determines the outcome.
6. **Headers** — + `X-Idempotency-Key`.
7. **Body** — optional `source` (`free|ad|purchase`, default `free`).
8. **Success** — `200 OK`

```json
{ "status":"success","message":"Spin complete.",
  "data":{ "segment_id":"seg_3","reward_type":"coins","reward_coins":50,
           "new_balance":4330,"spins_remaining":1,"transaction_uuid":"wt_.." },
  "meta":null,"errors":null,"request_id":"…","timestamp":"…" }
```

9. **Errors** — `LIMIT_REACHED (429)` (daily spins exhausted), `FRAUD_HOLD (403)`.
10. **Validation** — Daily count checked against `spin_history` for the UTC date.
11. **Business Rules** — Outcome by weighted random over active `spin_wheel_segments`; client animation must land on returned segment. Credits (if coins) via one ledger row (`type=spin`). `ad`-sourced spins require a verified rewarded-ad (tie to Ads module) before granting.
12. **Rate Limits** — 30 / min / user (plus daily spin limit business rule).
13. **Idempotency** — Client key prevents accidental double-spin from retries; each *distinct* spin is a new event.
14. **Security** — Server-authoritative outcome.
15. **DB Tables** — `spin_wheel_segments`, `spin_history`, `wallets`, `wallet_transactions`.

### 2.29 GET /v1/spin/history

5. **Purpose** — Spin history (paginated).
8. **Success** — list of spins with rewards.
15. **DB Tables** — `spin_history`.

---

## MODULE: Tasks

Base: `/v1/tasks` · Auth: User JWT · DB: `tasks`, `task_completions`, `wallets`, `wallet_transactions`.

### 2.30 GET /v1/tasks

5. **Purpose** — List active, in-window tasks.
7. **Query** — Pagination; `filter[task_type]`; sort `sort_order`.
8. **Success** — `200 OK` → `{ data:[ { uuid:"t_1", title, description, task_type, reward_coins, action_url, icon_url, per_user_limit, my_completions:0 } ] }`.
15. **DB Tables** — `tasks`, `task_completions`.

### 2.31 GET /v1/tasks/{uuid}

5. **Purpose** — Task detail.
9. **Errors** — `NOT_FOUND (404)`.
15. **DB Tables** — `tasks`, `task_completions`.

### 2.32 POST /v1/tasks/{uuid}/start

5. **Purpose** — Mark a task started (attribution).
8. **Success** — `200 OK` → `{ data: { completion_uuid:"tc_1", status:"started" } }`.
9. **Errors** — `LIMIT_REACHED (429)` (per-user limit), `NOT_FOUND (404)`, `RESOURCE_CONFLICT (409)` (outside window/inactive).
11. **Business Rules** — Creates `task_completions` (status `started`); respects `per_user_limit`/`max_completions`.
12. **Rate Limits** — 30 / min / user.
15. **DB Tables** — `tasks`, `task_completions`.

### 2.33 POST /v1/tasks/{uuid}/complete ⭐

5. **Purpose** — Submit completion for verification/crediting.
6. **Headers** — + `X-Idempotency-Key`; may be `multipart/form-data` if `proof` file.
7. **Body** — optional `proof` (file), optional `verification_ref` (string).
8. **Success** — `200 OK`
   - auto-verified: `{ data:{ status:"credited", coins_awarded:100, new_balance:4430, transaction_uuid } }`
   - manual: `{ data:{ status:"pending" } }` (`202 Accepted`).
9. **Errors** — `RESOURCE_CONFLICT (409)`, `LIMIT_REACHED (429)`, `DUPLICATE_REQUEST (409)`.
10. **Validation** — Proof file constraints as avatar (type/size).
11. **Business Rules** — `verification_type`: `auto` credits immediately; `manual` → admin queue (`pending`); `callback` awaits provider. Credit once via ledger `reference_id=task:<completion_uuid>`; idempotent by `uq_tc_user_task_ref`.
12. **Rate Limits** — 30 / min / user.
13. **Idempotency** — Ledger + `verification_ref` uniqueness prevents double credit.
14. **Security** — Reward snapshot from task at completion; server-authoritative.
15. **DB Tables** — `tasks`, `task_completions`, `wallets`, `wallet_transactions`.

### 2.34 GET /v1/tasks/history

5. **Purpose** — Completed/pending tasks (paginated).
7. **Query** — `filter[status]`.
15. **DB Tables** — `task_completions`, `tasks`.

---

## MODULE: Offerwall

Base: `/v1/offerwall` · Auth: User JWT (postbacks are Signed) · DB: `offerwall_providers`, `offers`, `offer_clicks`, `offer_conversions`, `postback_logs`, `wallets`, `wallet_transactions`.

### 2.35 GET /v1/offerwall/providers

5. **Purpose** — List active offerwall providers (display only).
8. **Success** — `200 OK` → `{ data:[ { slug:"adgate", name:"AdGate", logo_url } ] }`.
14. **Security** — Secrets/keys never returned.
15. **DB Tables** — `offerwall_providers`.

### 2.36 GET /v1/offerwall/offers

5. **Purpose** — Aggregated/curated offers, geo/platform-filtered.
7. **Query** — Pagination; `filter[provider]`, `filter[category]`; sort `payout_coins`, `-created_at`.
8. **Success** — `200 OK` → list of offers with `payout_coins`, `title`, `icon_url`, `category`.
11. **Business Rules** — Filters by user `country_code`/platform; only active offers of enabled providers.
15. **DB Tables** — `offers`, `offerwall_providers`.

### 2.37 GET /v1/offerwall/offers/{uuid}

5. **Purpose** — Offer detail.
9. **Errors** — `NOT_FOUND (404)`.
15. **DB Tables** — `offers`, `offerwall_providers`.

### 2.38 POST /v1/offerwall/offers/{uuid}/click

5. **Purpose** — Record a click and return the tracking/redirect URL.
6. **Headers** — + `X-Idempotency-Key` (optional).
8. **Success** — `200 OK` → `{ data: { redirect_url: "https://provider/…?s=<click_token>", click_token: "ct_…" } }`.
9. **Errors** — `NOT_FOUND (404)`, `RATE_LIMITED (429)`.
11. **Business Rules** — Creates `offer_clicks` with a unique `click_token` embedded in the redirect for postback attribution. High click velocity flagged.
12. **Rate Limits** — 60 / min / user (fraud velocity monitored).
14. **Security** — `click_token` is the attribution join; IP/device captured.
15. **DB Tables** — `offer_clicks`, `offers`, `offerwall_providers`.

### 2.39 POST /v1/postback/offerwall/{provider} ⭐ (Signed)

1–3. Offerwall · `/v1/postback/offerwall/{provider}` · POST (also GET per provider) 
4. **Auth** — **Signed** (HMAC signature + IP allowlist). Not user/JWT.
5. **Purpose** — Server-to-server conversion callback that triggers crediting.
6. **Request Headers** — Provider-specific signature header (e.g., `X-Signature`); `Content-Type` per provider (often form/query).
7. **Request Body / Query** (provider-mapped)

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `transaction_id` | string | Yes | Provider unique txn id (idempotency). |
| `click_token` (or `sub_id`) | string | Yes | Maps to `offer_clicks.click_token` → user. |
| `payout` | number | Yes | Provider payout (converted to coins). |
| `offer_id` | string | No | Provider offer id. |
| `status` | string | No | `credited`/`reversed`/`chargeback`. |
| `signature` | string | Yes | HMAC hash to verify. |

8. **Success** — `200 OK` (provider-expected body, often plain `OK` or JSON) — envelope may be simplified to match provider expectations; internally logged.
9. **Errors** — `INVALID_SIGNATURE (401)`, `IP_NOT_ALLOWED (403)`, `DUPLICATE_REQUEST (409)` (duplicate txn → still `200` acknowledged to provider but no re-credit), `NOT_FOUND (404)` (unknown click/user), `VALIDATION_ERROR (422)`.
10. **Validation** — Verify HMAC using provider `postback_secret`; verify source IP against `ip_allowlist`; payout matches configured offer within tolerance.
11. **Business Rules** — Idempotent by `(provider_id, transaction_id)` (`offer_conversions.uq_ocv_provider_txn`). On new valid conversion: create `offer_conversions` (status credited), credit user via ledger (`type=offerwall`, `reference_id=offerwall:<provider>:<txn_id>`), fire referral commission + notification + leaderboard update. `reversed`/`chargeback` → compensating `reversal` ledger row. **Every** postback (valid/invalid/dup) written to `postback_logs`.
12. **Rate Limits** — Provider-scoped high ceiling; abusive IPs blocked.
13. **Idempotency** — Mandatory; duplicate txn never double-credits.
14. **Security** — Signature + IP allowlist; never trust `payout` blindly (cross-check offer); fraud engine gate before credit.
15. **DB Tables** — `offer_conversions`, `offer_clicks`, `offers`, `offerwall_providers`, `postback_logs`, `wallets`, `wallet_transactions`, `referrals`, `referral_earnings`, `notifications`, `leaderboard_entries`, `fraud_flags`.

---

## MODULE: CPA Offers

Base: `/v1/cpa` · DB: `cpa_offers`, `offerwall_providers`, `offer_conversions`.

### 2.40 GET /v1/cpa/offers

4. **Auth** — User JWT. 5. **Purpose** — CPA offer catalog. 7. **Query** — Pagination; `filter[provider]`, `filter[country]`.
8. **Success** — list of CPA offers (`title`, `goal`, `payout_coins`, `tracking_url`).
15. **DB Tables** — `cpa_offers`, `offerwall_providers`.

### 2.41 GET /v1/cpa/offers/{uuid}

4. User JWT. 5. CPA offer detail. 9. `NOT_FOUND (404)`. 15. `cpa_offers`.

### 2.42 POST /v1/postback/cpa/{provider} ⭐ (Signed)

Identical contract to §2.39 (Offerwall postback), applied to CPA. Idempotent by `(provider_id, transaction_id)`, credits via ledger (`type=cpa`, `reference_id=cpa:<provider>:<txn_id>`), logs to `postback_logs`. **DB Tables** — `offer_conversions`, `cpa_offers`, `offerwall_providers`, `postback_logs`, `wallets`, `wallet_transactions`, `referrals`, `referral_earnings`.

---

## MODULE: Referral

Base: `/v1/referral` · Auth: User JWT · DB: `referrals`, `referral_config`, `referral_earnings`, `users`.

### 2.43 GET /v1/referral

5. **Purpose** — User's referral code, link, and stats.
8. **Success** — `200 OK` → `{ data: { referral_code:"ASHA12", referral_link:"https://cashnest.app/r/ASHA12", total_referrals:8, qualified:5, total_earned_coins:1200, commission_percent:"0.1000" } }`.
15. **DB Tables** — `referrals`, `referral_earnings`, `referral_config`, `users`.

### 2.44 GET /v1/referral/list

5. **Purpose** — List referred users + status (paginated).
7. **Query** — Pagination; `filter[status]`.
8. **Success** — `200 OK` → `{ data:[ { referee_name:"Ravi", status:"qualified", joined_at:"…" } ] }` (referee PII minimized).
15. **DB Tables** — `referrals`, `users`.

### 2.45 GET /v1/referral/earnings

5. **Purpose** — Referral commission history (paginated).
8. **Success** — list of `referral_earnings` with `commission_coins`, source.
15. **DB Tables** — `referral_earnings`.

### 2.46 POST /v1/referral/apply

5. **Purpose** — Apply a referral code (new user, once).
7. **Body** — `referral_code` (req, 6–12).
8. **Success** — `200 OK` → `{ data: { applied:true } }`.
9. **Errors** — `VALIDATION_ERROR (422)`, `RESOURCE_CONFLICT (409)` (already referred), `FORBIDDEN (403)` (self-referral / not eligible).
10. **Validation** — Code exists; user not already referred (`referrals.uq_ref_referee`).
11. **Business Rules** — `referrer_id != referee_id`; same device/IP → `fraud_flags` + possible rejection. Bonuses granted on qualification per `referral_config`, credited once (ledger guarded).
12. **Rate Limits** — 5 / day / user.
14. **Security** — Self-referral and device-collusion detection.
15. **DB Tables** — `referrals`, `referral_config`, `users`, `fraud_flags`.

---

## MODULE: Leaderboard

Base: `/v1/leaderboard` · Auth: User JWT · DB: `leaderboard_periods`, `leaderboard_entries`, `users`.

### 2.47 GET /v1/leaderboard

5. **Purpose** — Rankings for a period.
7. **Query** — `filter[period]` = `daily|weekly|monthly|all_time` (default `weekly`); Pagination.
8. **Success** — `200 OK` → `{ data:[ { rank:1, user:{ name:"Asha", avatar_url }, score:12000 } ], meta:{ period:"weekly", period_key:"2026-W30" } }`.
11. **Business Rules** — Reads materialized `leaderboard_entries`; ranks recomputed by cron. PII minimized (display name + avatar only).
12. **Rate Limits** — 120 / min / user.
15. **DB Tables** — `leaderboard_entries`, `leaderboard_periods`, `users`.

### 2.48 GET /v1/leaderboard/me

5. **Purpose** — Caller's rank + score for a period.
7. **Query** — `filter[period]`.
8. **Success** — `200 OK` → `{ data: { rank:42, score:3400, period:"weekly" } }`.
15. **DB Tables** — `leaderboard_entries`, `leaderboard_periods`.

---

## MODULE: Withdraw

Base: `/v1/withdraw` · Auth: User JWT · DB: `withdraw_methods`, `withdraw_requests`, `withdraw_history`, `payment_gateways`, `wallets`, `wallet_transactions`, `user_kyc`, `fraud_flags`.

### 2.49 GET /v1/withdraw/methods

5. **Purpose** — Available payout methods + limits/fees.
8. **Success** — `200 OK` → `{ data:[ { code:"upi", name:"UPI", min_coins:5000, max_coins:100000, fee_percent:"0.0000", detail_schema:{ upi_id:"string" } } ] }`.
15. **DB Tables** — `withdraw_methods`, `payment_gateways`.

### 2.50 POST /v1/withdraw/request ⭐

5. **Purpose** — Create a withdrawal request; reserves balance atomically.
6. **Headers** — + `X-Idempotency-Key` (**required**).
7. **Request Body**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `method_code` | string | Yes | Must be an active method. |
| `coins_amount` | integer | Yes | ≥ method.min_coins, ≤ available balance & max. |
| `payment_detail` | object | Yes | Must satisfy method `detail_schema` (e.g., `upi_id`). |

8. **Success** — `201 Created`

```json
{ "status":"success","message":"Withdrawal requested.",
  "data":{ "uuid":"wr_1","status":"pending","coins_amount":5000,
           "cash_amount":"5.0000","fee_amount":"0.0000","net_amount":"5.0000",
           "currency":"INR","conversion_rate":"0.00100000",
           "hold_transaction_uuid":"wt_h1","created_at":"…" },
  "meta":null,"errors":null,"request_id":"…","timestamp":"…" }
```

9. **Error Responses**

| HTTP | Code | Message |
|------|------|---------|
| 422 | `INSUFFICIENT_BALANCE` | Not enough coins available. |
| 422 | `MIN_WITHDRAW_NOT_MET` | Below minimum withdrawal. |
| 403 | `KYC_REQUIRED` | Complete KYC to withdraw. |
| 403 | `FRAUD_HOLD` | Withdrawals are temporarily on hold. |
| 409 | `DUPLICATE_REQUEST` | Idempotency key reused with different payload. |
| 422 | `VALIDATION_ERROR` | Invalid method/detail. |

10. **Validation** — `coins_amount` within `[min, min(max, available)]`; `payment_detail` validated against schema; currency thresholds from `currency_settings`.
11. **Business Rules** — In one locked transaction: verify available balance, **reserve** coins (increment `wallets.coin_reserved`, write `withdrawal_hold` ledger row `reference_id=withdraw_hold:<request_uuid>`), snapshot `conversion_rate`, create `withdraw_requests` (status `pending`), write initial `withdraw_history` row. KYC gate above threshold. Open high/critical `fraud_flags` block the request.
12. **Rate Limits** — 5 / hour / user.
13. **Idempotency** — Mandatory client key; ledger `reference_id` prevents duplicate holds.
14. **Security** — Server computes cash/fees; client cannot set amounts/rates. Dual-control for large amounts handled admin-side.
15. **DB Tables** — `withdraw_requests`, `withdraw_history`, `withdraw_methods`, `payment_gateways`, `wallets`, `wallet_transactions`, `user_kyc`, `fraud_flags`, `currency_settings`.

### 2.51 GET /v1/withdraw/history

5. **Purpose** — Paginated withdrawal history + statuses.
7. **Query** — Pagination; `filter[status]`, date range.
8. **Success** — list of requests with status timeline summary.
15. **DB Tables** — `withdraw_requests`.

### 2.52 GET /v1/withdraw/{uuid}

5. **Purpose** — Single request detail + status timeline.
8. **Success** — request object + `history: [ { from_status, to_status, note, created_at } ]`.
9. **Errors** — `NOT_FOUND (404)`.
15. **DB Tables** — `withdraw_requests`, `withdraw_history`.

### 2.53 POST /v1/withdraw/{uuid}/cancel

5. **Purpose** — Cancel a pending request; release the hold.
8. **Success** — `200 OK` → `{ data: { status:"cancelled", refunded_coins:5000, new_balance:4200 } }`.
9. **Errors** — `RESOURCE_CONFLICT (409)` (not pending — already processing/paid), `NOT_FOUND (404)`.
11. **Business Rules** — Only `pending` cancellable; releases reserve via compensating ledger row (`withdrawal_release`, `reference_id=withdraw_release:<request_uuid>`); writes `withdraw_history`.
13. **Idempotency** — Re-cancel of a cancelled request returns current state idempotently.
15. **DB Tables** — `withdraw_requests`, `withdraw_history`, `wallets`, `wallet_transactions`.

---

## MODULE: Notifications

Base: `/v1/notifications` · Auth: User JWT · DB: `notifications`, `notification_campaigns`.

### 2.54 GET /v1/notifications

5. **Purpose** — Paginated in-app inbox.
7. **Query** — Pagination; `filter[type]`, `filter[is_read]`.
8. **Success** — `200 OK` → `{ data:[ { uuid, title, body, type, deep_link, image_url, is_read:false, created_at } ], meta:{ pagination } }`.
12. **Rate Limits** — 120 / min / user.
15. **DB Tables** — `notifications`.

### 2.55 GET /v1/notifications/unread-count

5. **Purpose** — Badge count.
8. **Success** — `200 OK` → `{ data: { unread: 5 } }` (served by composite index).
15. **DB Tables** — `notifications`.

### 2.56 POST /v1/notifications/{uuid}/read

5. **Purpose** — Mark one notification read.
8. **Success** — `200 OK` → `{ data: { uuid, is_read:true } }`.
9. **Errors** — `NOT_FOUND (404)`.
13. **Idempotency** — Idempotent (already-read returns success).
15. **DB Tables** — `notifications`.

### 2.57 POST /v1/notifications/read-all

5. **Purpose** — Mark all read.
8. **Success** — `200 OK` → `{ data: { updated: 12 } }`.
12. **Rate Limits** — 10 / min / user.
15. **DB Tables** — `notifications`.

---

## MODULE: Settings

Base: `/v1/settings` · DB: `user_settings`, `app_settings`, `app_versions`, `maintenance_windows`.

### 2.58 GET /v1/settings

4. User JWT. 5. **Purpose** — User preferences + public config.
8. **Success** — `200 OK` → `{ data: { preferences:{ notif_push_enabled:true, notif_promotional:true, language:"en", theme_mode:"system" }, app_config:{ …public flags… } } }`.
15. **DB Tables** — `user_settings`, `app_settings`.

### 2.59 PUT /v1/settings

4. User JWT. 5. **Purpose** — Update preferences.
7. **Body** — `notif_push_enabled` (bool), `notif_transactional` (bool), `notif_promotional` (bool), `language` (string), `theme_mode` (enum) — all optional.
8. **Success** — `200 OK` → updated preferences.
10. **Validation** — Enum/lang whitelist.
15. **DB Tables** — `user_settings`.

### 2.60 GET /v1/settings/app  (and /v1/app/version, /v1/app/maintenance)

4. **Auth** — Public. 5. **Purpose** — Public app config: version + maintenance gate (also exposed as dedicated App Version endpoints §2.75–2.76).
8. **Success** — `200 OK` → `{ data: { latest_version:"1.5.0", min_supported_code:120, force_update:false, maintenance:{ enabled:false, message:null } } }`.
9. **Errors** — May return `MAINTENANCE_MODE (503)` semantics via the `maintenance` object (endpoint itself stays reachable so clients can read the gate).
15. **DB Tables** — `app_settings`, `app_versions`, `maintenance_windows`.

---

## MODULE: Support

Base: `/v1/support` · DB: `support_tickets`, `support_messages`, `faqs`, `faq_categories`.

### 2.61 GET /v1/support/faqs

4. **Auth** — Public. 5. **Purpose** — FAQ list grouped by category.
7. **Query** — `filter[category]`, `Accept-Language` for locale.
8. **Success** — `200 OK` → `{ data:[ { category:"Withdrawals", items:[ { question, answer } ] } ] }`.
15. **DB Tables** — `faqs`, `faq_categories`.

### 2.62 GET /v1/support/tickets

4. User JWT. 5. **Purpose** — User's tickets (paginated). 7. `filter[status]`.
8. list of tickets. 15. `support_tickets`.

### 2.63 POST /v1/support/tickets

4. User JWT. 5. **Purpose** — Create a ticket.
7. **Body** — `subject` (req, ≤200), `category` (opt), `message` (req, text), optional `attachment` (file).
8. **Success** — `201 Created` → `{ data: { uuid, status:"open" } }`.
9. **Errors** — `VALIDATION_ERROR (422)`, `PAYLOAD_TOO_LARGE (413)`.
11. **Business Rules** — Creates `support_tickets` + first `support_messages` (sender_type user).
12. **Rate Limits** — 10 / hour / user.
15. **DB Tables** — `support_tickets`, `support_messages`.

### 2.64 GET /v1/support/tickets/{uuid}

4. User JWT. 5. Ticket thread. 9. `NOT_FOUND (404)` (ownership). 15. `support_tickets`, `support_messages`.

### 2.65 POST /v1/support/tickets/{uuid}/reply

4. User JWT. 5. Add a message to a ticket.
7. **Body** — `message` (req), optional `attachment`.
8. **Success** — `201 Created` → message object.
9. **Errors** — `RESOURCE_CONFLICT (409)` (ticket closed), `NOT_FOUND (404)`.
11. **Business Rules** — Appends `support_messages`; updates `last_reply_at`, reopens if answered.
15. **DB Tables** — `support_messages`, `support_tickets`.

---

## MODULE: Ads (Multi-Network)

Base: `/v1/ads` · DB: `ads_placements`, `ad_networks`, `ad_units`, `ad_network_events`, `wallets`, `wallet_transactions`.

### 2.66 GET /v1/ads/placements

4. User JWT. 5. **Purpose** — Ad unit config per placement (legacy shape).
8. **Success** — `200 OK` → `{ data:[ { code:"home_banner", ad_format:"banner", reward_coins:0 } ] }`.
15. **DB Tables** — `ads_placements`.

### 2.67 GET /v1/ads/config

4. User JWT. 5. **Purpose** — Active network + unit IDs per placement (mediation waterfall).
7. **Query** — optional `filter[placement]`.
8. **Success** — `200 OK`

```json
{ "status":"success","message":"OK",
  "data":[ { "placement":"spin_rewarded","ad_format":"rewarded","reward_coins":20,
    "daily_cap_per_user":5,
    "waterfall":[ { "network":"admob","ad_unit_id":"ca-app-pub-…","priority":1 },
                  { "network":"applovin_max","ad_unit_id":"max_…","priority":2 } ] } ],
  "meta":null,"errors":null,"request_id":"…","timestamp":"…" }
```

11. **Business Rules** — Only **enabled** networks/units, ordered by priority, filtered by user country. Returns **public unit IDs only** (never secrets).
14. **Security** — No API keys/secrets exposed; disabling a network in admin removes it here instantly.
15. **DB Tables** — `ad_networks`, `ad_units`, `ads_placements`.

### 2.68 POST /v1/ads/impression

4. User JWT. 5. **Purpose** — Log an impression (analytics/fraud).
7. **Body** — `placement` (req), `network` (opt), `ad_unit_id` (opt), `event_type` (default `impression`).
8. **Success** — `202 Accepted` → `{ data:{ logged:true } }`.
12. **Rate Limits** — 120 / min / user.
15. **DB Tables** — `ad_network_events`.

### 2.69 POST /v1/ads/rewarded/verify ⭐ (also legacy `/v1/ads/rewarded/complete`)

5. **Purpose** — Verify a rewarded-ad completion and credit coins.
6. **Headers** — + `X-Idempotency-Key`.
7. **Request Body**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `placement` | string | Yes | Must be a rewarded placement. |
| `network` | enum | Yes | `admob`\|`applovin_max`\|`unity_ads`. |
| `ssv_token` | string | Yes | Server-side-verification token/nonce. |
| `ad_unit_id` | string | No | Unit shown. |

8. **Success** — `200 OK` → `{ data: { verified:true, coins_awarded:20, new_balance:4250, transaction_uuid } }`.
9. **Errors** — `VALIDATION_ERROR (422)`, `LIMIT_REACHED (429)` (daily cap), `DUPLICATE_REQUEST (409)` (ssv_token reused), `FRAUD_HOLD (403)`, `SERVICE_UNAVAILABLE (503)` (verification upstream down).
10. **Validation** — SSV verified against the network's callback/nonce; daily cap from `ads_placements.daily_cap_per_user`.
11. **Business Rules** — Credit **only** when `ssv_verified=1`; idempotent by `ad_network_events.uq_ane_ssv_token` + ledger `reference_id=rewarded_ad:<ssv_token>`. Writes `ad_network_events` + ledger row (`type=rewarded_ad`).
12. **Rate Limits** — 30 / min / user + daily cap.
13. **Idempotency** — Mandatory; replayed `ssv_token` never re-credits.
14. **Security** — Closes fake-reward exploit — client claim alone never credits.
15. **DB Tables** — `ad_network_events`, `ad_networks`, `ad_units`, `ads_placements`, `wallets`, `wallet_transactions`.

---

## MODULE: Banner

### 2.70 GET /v1/banners

1–4. Banner · `/v1/banners` · GET · **Public** (User JWT optional for audience targeting)
5. **Purpose** — Active banners for a placement, audience-filtered.
7. **Query** — `filter[placement]` (default `home_top`).
8. **Success** — `200 OK` → `{ data:[ { uuid, image_url, title, action_type:"deep_link", action_value:"cashnest://spin", sort_order:0 } ] }`.
11. **Business Rules** — Only active, in-window banners; `action_value` from an allowlisted scheme; audience filter applied when authenticated.
12. **Rate Limits** — 120 / min (IP/user).
14. **Security** — Deep-link/URL allowlist prevents open redirects.
15. **DB Tables** — `banners`.

---

## MODULE: Announcement

Base: `/v1/announcements` · DB: `announcements`, `announcement_reads`.

### 2.71 GET /v1/announcements

4. **Auth** — User JWT (Public allowed for global bar). 5. **Purpose** — Active announcements for the user.
8. **Success** — `200 OK` → `{ data:[ { uuid, title, body, display_type:"popup", priority:100, is_dismissible:true, action_type, action_value } ] }`.
11. **Business Rules** — Excludes already-seen (via `announcement_reads`) when authenticated; ordered by priority; in-window only.
15. **DB Tables** — `announcements`, `announcement_reads`.

### 2.72 POST /v1/announcements/{uuid}/seen

4. User JWT. 5. **Purpose** — Mark an announcement seen/dismissed.
8. **Success** — `200 OK` → `{ data:{ seen:true } }`.
9. **Errors** — `NOT_FOUND (404)`.
13. **Idempotency** — Upsert on `(announcement_id, user_id)` — idempotent.
15. **DB Tables** — `announcement_reads`.

---

## MODULE: CMS

Base: `/v1/cms` · Auth: Public · DB: `cms_pages`, `faqs`, `faq_categories`.

### 2.73 GET /v1/cms/{slug}

5. **Purpose** — Fetch a content page (`privacy-policy`, `terms`, `about`, …).
6. **Headers** — `Accept-Language` selects locale.
7. **Path** — `slug`.
8. **Success** — `200 OK` → `{ data: { slug, title, body, locale:"en", version:3, effective_at:"…" } }`.
9. **Errors** — `NOT_FOUND (404)` (unknown/unpublished slug).
11. **Business Rules** — Returns latest **published** version for slug+locale; falls back to `en` if locale missing.
12. **Rate Limits** — 120 / min (IP).
14. **Security** — Body pre-sanitized on save; safe to render.
15. **DB Tables** — `cms_pages`.

### 2.74 GET /v1/cms/faq

5. **Purpose** — FAQ entries grouped by category (public).
8. **Success** — `200 OK` → grouped FAQ list (same shape as §2.61).
15. **DB Tables** — `faqs`, `faq_categories`.

---

## MODULE: App Version

Base: `/v1/app` · Auth: Public · DB: `app_versions`, `maintenance_windows`.

### 2.75 GET /v1/app/version

5. **Purpose** — Latest version + force-update decision for the caller's platform.
6. **Headers** — `X-Platform`, `X-App-Version` used for evaluation.
7. **Query** — optional `platform`.
8. **Success** — `200 OK`

```json
{ "status":"success","message":"OK",
  "data":{ "platform":"android","latest_version":"1.5.0","latest_version_code":150,
    "min_supported_code":120,"force_update":false,"update_available":true,
    "store_url":"https://play.google.com/…","changelog":"Bug fixes" },
  "meta":null,"errors":null,"request_id":"…","timestamp":"…" }
```

9. **Errors** — `FORCE_UPDATE_REQUIRED (426)` may be returned by **other** endpoints when the client is below `min_supported_code`; this endpoint always returns `200` so the client can read the gate.
11. **Business Rules** — `force_update=true` + client `version_code < min_supported_code` → client must block usage.
15. **DB Tables** — `app_versions`.

### 2.76 GET /v1/app/maintenance

5. **Purpose** — Maintenance-mode status + message.
8. **Success** — `200 OK` → `{ data: { enabled:false, title:null, message:null, scheduled_start:null, scheduled_end:null } }`.
11. **Business Rules** — When enabled, protected earn/withdraw endpoints return `MAINTENANCE_MODE (503)`; this endpoint stays reachable.
15. **DB Tables** — `maintenance_windows`.

---

## MODULE: Remote Config

### 2.77 GET /v1/config

1–4. Remote Config · `/v1/config` · GET · **Public** (User JWT optional for segment targeting)
5. **Purpose** — Effective remote config / feature flags for the client.
6. **Headers** — `X-App-Version`, `X-Platform`, optional `Authorization` (for %-rollout by user).
7. **Query** — optional `keys` (comma-separated subset).
8. **Success** — `200 OK`

```json
{ "status":"success","message":"OK",
  "data":{ "spin_enabled":true, "max_daily_spins":3, "offerwall_enabled":true,
           "min_app_version":"1.2.0", "feature_new_home":false },
  "meta":{ "etag":"cfg_v87" }, "errors":null, "request_id":"…","timestamp":"…" }
```

9. **Errors** — Standard.
10. **Validation** — Values typed per `remote_configs.value_type`.
11. **Business Rules** — Environment + audience-segment resolution; **read-only** to clients; flags never override server-side money math.
12. **Rate Limits** — 120 / min. Supports `ETag`/`If-None-Match` → `304 Not Modified`.
14. **Security** — Only active, client-safe keys returned.
15. **DB Tables** — `remote_configs` (and `app_settings` for legacy public keys).

---

## MODULE: Theme

### 2.78 GET /v1/theme

1–4. Theme · `/v1/theme` · GET · **Public**
5. **Purpose** — Active theme (colors, logo, font, default mode).
8. **Success** — `200 OK` → `{ data: { name:"Default", primary_color:"#1E88E5", secondary_color:"#42A5F5", accent_color:"#FFC107", background_color:"#FFFFFF", logo_url:"https://cdn/logo.png", font_family:"Inter", default_mode:"system", extra:{} }, meta:{ etag:"theme_v4" } }`.
11. **Business Rules** — Returns the single active theme; client caches with a safe fallback.
12. **Rate Limits** — 120 / min. Supports `ETag` → `304`.
15. **DB Tables** — `themes`.

---

## MODULE: Payment Gateway

### 2.79 GET /v1/payment/gateways

1–4. Payment Gateway · `/v1/payment/gateways` · GET · User JWT
5. **Purpose** — Active gateways available for payout + limits/fees (selection surfaced in Withdraw).
8. **Success** — `200 OK` → `{ data:[ { code:"upi", name:"UPI", fee_percent:"0.0000", fee_flat:"0.0000", min_amount:"5.0000", max_amount:"1000.0000", currencies:["INR"] } ] }`.
11. **Business Rules** — Only enabled gateways; mapped to withdraw methods. Execution occurs server-side during withdrawal processing (`gateway_transactions`).
14. **Security** — Credentials never exposed; public config only.
15. **DB Tables** — `payment_gateways`, `withdraw_methods`.

---

## MODULE: Home Layout

### 2.80 GET /v1/home/layout

1–4. Home Layout · `/v1/home/layout` · GET · User JWT (Public allowed for anonymous preview)
5. **Purpose** — Ordered, server-driven home-screen sections + config.
8. **Success** — `200 OK`

```json
{ "status":"success","message":"OK",
  "data":[
    { "type":"banner_carousel","title":null,"config":{ "placement":"home_top" },"sort_order":0 },
    { "type":"quick_actions","title":"Earn","config":{ "actions":["spin","scratch","checkin"] },"sort_order":1 },
    { "type":"offers","title":"Top Offers","config":{ "limit":5 },"sort_order":2 }
  ],
  "meta":{ "etag":"home_v12" }, "errors":null, "request_id":"…","timestamp":"…" }
```

11. **Business Rules** — Active, in-window, audience-filtered sections ordered by `sort_order`. Unknown `type` values must be **ignored gracefully** by clients (forward-compatible).
12. **Rate Limits** — 120 / min. Supports `ETag` → `304`.
15. **DB Tables** — `home_sections`.

---

# PART 3 — ADMIN API (Overview)

**Auth:** Admin Session (cookie + `X-CSRF-Token`), RBAC-checked (`admin_permissions`), every mutation written to `admin_audit_logs`. Base: `/api`. These endpoints mirror the app modules with management operations. The consistent response/error envelope (§1.5–1.7) applies. Representative groups (per SAD §5.25–5.26):

| Module | Endpoints (representative) | Permission | DB Tables |
|--------|---------------------------|------------|-----------|
| Dashboard | `GET /api/dashboard/stats` | `dashboard.view` | aggregates across tables |
| Users | `GET/PUT /api/users`, `POST /api/users/{id}/ban` | `user.*` | `users`, `wallets`, `fraud_flags` |
| Wallet | `POST /api/users/{id}/wallet/adjust` (manual credit/debit, reason required) | `wallet.adjust` | `wallets`, `wallet_transactions` |
| Rewards | CRUD check-in/scratch/spin/tasks config | `reward.*` | `checkin_rewards_config`, `scratch_card_config`, `spin_wheel_segments`, `tasks` |
| Offerwall | CRUD providers/offers, view conversions/postbacks | `offerwall.*` | `offerwall_providers`, `offers`, `offer_conversions`, `postback_logs` |
| Ad Networks | CRUD networks/units, `PUT /api/ad-networks/{id}/toggle` | `ads.*` | `ad_networks`, `ad_units`, `ads_placements` |
| Payments | CRUD gateways, toggle, view `gateway_transactions` | `payment.*` | `payment_gateways`, `gateway_transactions` |
| Withdraw | queue, `POST /api/withdrawals/{id}/approve|reject|paid` | `withdraw.approve` | `withdraw_requests`, `withdraw_history`, `gateway_transactions` |
| Referral | config, trees, abuse | `referral.*` | `referral_config`, `referrals`, `referral_earnings` |
| Notifications | compose/schedule campaigns | `notification.send` | `notification_campaigns`, `notifications` |
| Banners/Announcements/Home/Theme/CMS | CRUD + activate/reorder | respective perms | `banners`, `announcements`, `home_sections`, `themes`, `cms_pages` |
| Remote Config | CRUD flags | `config.manage` | `remote_configs` |
| App Version/Maintenance | set version, toggle maintenance | `settings.manage` (Super Admin) | `app_versions`, `maintenance_windows` |
| Backup & Restore | `POST /api/backups`, `POST /api/backups/{id}/restore` (Super Admin, dual-confirm) | `backup.*` | `backup_jobs`, `restore_logs` |
| Support | ticket inbox, reply, assign | `support.*` | `support_tickets`, `support_messages` |
| KYC | review queue, approve/reject | `kyc.review` | `user_kyc` |

**Admin security (all endpoints):** session + CSRF, RBAC per action, re-auth + dual-confirmation for destructive ops (restore, credential edits, maintenance), secrets write-only/masked, full audit in `admin_audit_logs`.

---

# PART 4 — FLOW DIAGRAMS

## 4.1 Complete API Flow (high level)

```
                 ┌──────────────┐        ┌──────────────┐
   Flutter App ──┤  /v1/* (JWT) ├──────▶ │  PHP MVC API  │
                 └──────────────┘        │  Controllers  │
   Public   ─────▶ /v1/auth, /config,    │      │        │
                    /theme, /cms, /app…   │  Middleware   │
   Providers ─────▶ /v1/postback/*  ─────▶│ (JWT/RateLim/ │──▶ Services ──▶ Repositories ──▶ MySQL
                    (signed)              │  Validate/    │        │
   Admin    ─────▶ /api/* (session+CSRF) │  RBAC/Fraud)  │        └──▶ Events ─▶ FCM / Leaderboard / Referral
                 └──────────────────────┘
   Every response → standard envelope (§1.5); every error → standard codes (§1.7).
```

## 4.2 Authentication Sequence

```
App                         API                         Google         DB
 │  POST /v1/auth/google      │                            │            │
 │  { id_token }              │                            │            │
 │──────────────────────────▶│  verify id_token ─────────▶│            │
 │                            │◀───────────── verified ────│            │
 │                            │  upsert user/session ──────────────────▶│
 │                            │◀──────────── user row ─────────────────│
 │◀── 200 { access, refresh }─│                            │            │
 │                            │                            │            │
 │  GET /v1/wallet (Bearer)   │                            │            │
 │──────────────────────────▶│  validate JWT              │            │
 │◀── 200 { balance } ────────│                            │            │
 │                            │                            │            │
 │  (access expired) 401 TOKEN_EXPIRED                     │            │
 │  POST /v1/auth/refresh { refresh } ───────────────────▶│ rotate ───▶│
 │◀── 200 { new access, new refresh } ────────────────────│            │
```

## 4.3 Wallet Transaction Flow (earn — generic)

```
Earn trigger (checkin/spin/scratch/task/offerwall/rewarded_ad)
        │
        ▼
[Service] BEGIN TRANSACTION
        │  SELECT wallet FOR UPDATE           (row lock)
        │  check fraud/limits/eligibility
        ▼
[Ledger] INSERT wallet_transactions           (unique reference_id — idempotent)
        │      direction=credit, amount, balance_after
        ▼
[Wallet] UPDATE wallets                        (coin_balance += amount, version++)
        │
        ▼  COMMIT
        │
        ├─▶ Event: WalletCredited
        │       ├─ ReferralService.commission  (if applicable → referral_earnings + ledger)
        │       ├─ NotificationService.push     (notifications + FCM)
        │       └─ LeaderboardService.addScore  (leaderboard_entries)
        ▼
Response: { coins_awarded, new_balance, transaction_uuid }
   (duplicate reference_id → no second insert → returns existing result)
```

## 4.4 Withdraw Flow

```
User                         API                                 Admin
 │ POST /v1/withdraw/request  │                                    │
 │ (X-Idempotency-Key)        │  BEGIN TXN; wallet FOR UPDATE      │
 │───────────────────────────▶│  check: balance/min/KYC/fraud     │
 │                            │  RESERVE coins:                    │
 │                            │   INSERT ledger (withdrawal_hold)  │
 │                            │   wallets.coin_reserved += amount  │
 │                            │  INSERT withdraw_requests(pending) │
 │                            │  INSERT withdraw_history           │
 │                            │  COMMIT                            │
 │◀── 201 { status:pending } ─│                                    │
 │                            │        (admin reviews queue) ─────▶│
 │                            │◀── approve / reject ───────────────│
 │            ┌───────────────┴────────────────┐                  │
 │   approve+pay:                        reject/cancel:            │
 │   gateway_transactions(execute)       release hold:            │
 │   on success:                          INSERT ledger           │
 │     INSERT ledger(withdrawal_debit)    (withdrawal_release)    │
 │     coin_reserved -= amount            coin_reserved -= amount │
 │     status=paid                        status=rejected         │
 │   FCM notify user                     FCM notify user          │
 │◀── status via GET /v1/withdraw/{uuid} + history ───────────────│
```

## 4.5 Offerwall Conversion Flow

```
User taps offer                 API                         Provider
 │ POST /v1/offerwall/offers/{id}/click                        │
 │──────────────────────────────▶│ INSERT offer_clicks(token) │
 │◀── redirect_url (?s=token) ────│                            │
 │  opens provider ──────────────────────────────────────────▶│ (user completes offer)
 │                                                             │
 │                                │◀── POST /v1/postback/offerwall/{provider}
 │                                │     { transaction_id, click_token, payout, signature }
 │                                │  verify HMAC signature      │
 │                                │  verify source IP allowlist │
 │                                │  INSERT postback_logs (always)
 │                                │  idempotency check:         │
 │                                │   (provider_id, transaction_id) unique?
 │                                │     └ duplicate → ack 200, NO credit
 │                                │  resolve user via click_token
 │                                │  FraudEngine.check          │
 │                                │  BEGIN TXN:                 │
 │                                │   INSERT offer_conversions(credited)
 │                                │   INSERT ledger (type=offerwall, reference_id)
 │                                │   wallets += payout_coins   │
 │                                │  COMMIT                     │
 │                                │  Events: referral + FCM + leaderboard
 │                                │── 200 OK ─────────────────▶│ (provider ack)
 │◀── (async) FCM: "You earned X coins!"                       │
```

---

*End of API Specification — CashNest v1.0. Consistent envelope (§1.5), reusable error codes (§1.7), and idempotency guarantees (§1.11) apply across all endpoints. No PHP, Flutter, or SQL generated; architecture and database designs unchanged.*
