# CashNest — Production Database Design (MySQL)

> **Companion to:** `ARCHITECTURE.md` (SAD v1.1 — FINAL, not modified by this document)
> **Role:** Lead Database Architect
> **Engine:** MySQL 8.x / **InnoDB**
> **Purpose:** Complete, production-ready schema *design* for every table in the architecture. This is a design specification — **no SQL, no DDL, no migrations**.

---

## 0. Global Design Conventions

These conventions apply to **every** table unless a table's own notes override them. They are stated once here to keep per-table sections focused.

### 0.1 Engine & Encoding
- **Storage engine:** InnoDB (row-level locking, ACID transactions, foreign keys) — mandatory for financial integrity.
- **Character set / collation:** `utf8mb4` / `utf8mb4_0900_ai_ci` (full Unicode incl. emoji; case-insensitive).
- **Row format:** `DYNAMIC`.
- **Time zone:** all datetimes stored in **UTC**; conversion happens at the application/presentation layer.

### 0.2 Primary Keys
- Every table has a surrogate PK named `id`, type **`BIGINT UNSIGNED AUTO_INCREMENT`**, unless it is a pure junction table (composite PK) — noted where applicable.
- Public-facing identifiers (referral codes, request references, share tokens) are **separate** opaque columns, never the numeric PK, to avoid enumeration.

### 0.3 Foreign Keys
- All FK columns are `BIGINT UNSIGNED` matching their parent `id`.
- Default referential actions: **`ON UPDATE CASCADE`**. `ON DELETE` is chosen per relationship:
  - `RESTRICT` for financial/audit parents (never allow deleting a user who has ledger rows; use soft-delete/anonymize instead).
  - `CASCADE` only for owned child rows that are meaningless without the parent and carry no financial value (e.g., `announcement_reads`).
  - `SET NULL` for optional references (e.g., a transaction's optional `admin_id`).
- FK behavior is stated explicitly in each table's **Foreign Keys** section.

### 0.4 Standard Timestamp Columns
Unless noted, every table includes:
| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Row creation time (UTC). |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Last modification time (UTC). |

Tables that support soft delete additionally carry `deleted_at DATETIME NULL DEFAULT NULL` (a non-null value means logically deleted); this is noted per table.

### 0.5 Money & Currency Representation (critical)
- **Coins** (the in-app point currency): integer, stored as **`BIGINT`**. Balances are `BIGINT UNSIGNED` (cannot go negative); ledger deltas are signed `BIGINT`.
- **Cash** (real-money equivalents: withdrawals, gateway fees): **`DECIMAL(18,4)`** — never `FLOAT`/`DOUBLE`, to eliminate rounding error.
- **Conversion rate** (coins → cash): `DECIMAL(18,8)`.
- **Percentages** (commissions, fees): `DECIMAL(6,4)` (e.g., 0.1000 = 10%).

### 0.6 Enumerations
- Small, stable value sets use MySQL **`ENUM`** for storage efficiency and self-documentation (e.g., `status`, `type`).
- Any set expected to grow at runtime (e.g., offer categories) uses `VARCHAR` + application validation or a lookup table — noted where relevant.

### 0.7 Booleans & Flags
- Booleans stored as **`TINYINT(1) UNSIGNED`**, default `0` (or `1` where the safe default is "enabled"), never nullable.

### 0.8 Indexing Philosophy
- Index every FK column (InnoDB does not auto-create these).
- Composite indexes ordered by selectivity and query shape (equality columns first, range/sort last).
- Covering indexes on hot read paths (ledger history, notifications inbox, leaderboard).
- Unique constraints enforce business invariants (idempotency keys, one-row-per-user-per-period, natural keys).

### 0.9 Concurrency & Integrity
- All balance mutations occur inside a single DB transaction with `SELECT ... FOR UPDATE` on the wallet row.
- Idempotency enforced by unique keys on external/reference identifiers (postbacks, transactions).
- Isolation level: **READ COMMITTED** for general traffic; financial mutation paths rely on explicit row locks rather than higher isolation to minimize lock contention.

### 0.10 Retention & Partitioning (forward-looking)
- High-volume append-only tables (`wallet_transactions`, `postback_logs`, `ad_network_events`, `offer_clicks`, `notifications`) are **partition candidates** by `created_at` (monthly `RANGE`) and archival targets. Noted per table; not applied at v1 on shared hosting.

---

## Table Directory

| # | Domain | Tables |
|---|--------|--------|
| A | Identity & Users | users, user_auth_providers, user_devices, user_sessions, user_kyc, user_settings |
| B | Wallet & Currency | wallets, wallet_transactions, currency_settings |
| C | Reward Mechanics | daily_checkins, checkin_rewards_config, scratch_cards, scratch_card_config, spin_wheel_segments, spin_history, tasks, task_completions |
| D | Offerwall & CPA | offerwall_providers, offers, offer_clicks, offer_conversions, cpa_offers, postback_logs |
| E | Referral | referrals, referral_config, referral_earnings |
| F | Leaderboard | leaderboard_periods, leaderboard_entries |
| G | Withdrawals | withdraw_methods, withdraw_requests, withdraw_history |
| H | Engagement & System | notifications, notification_campaigns, support_tickets, support_messages, faqs, faq_categories, app_settings |
| I | Admin & Governance | admins, admin_roles, admin_permissions, admin_role_permissions, admin_audit_logs, fraud_flags |
| J | Ads (Multi-Network) | ads_placements, ad_networks, ad_units, ad_network_events |
| K | Payments | payment_gateways, gateway_transactions |
| L | Content & Config (server-driven) | banners, announcements, announcement_reads, app_versions, maintenance_windows, remote_configs, cms_pages, home_sections, themes |
| M | Operations | backup_jobs, restore_logs |

---

# A. Identity & Users

## A.1 `users`

**1. Table Purpose** — The central account record for every end user; the hub referenced by nearly all other tables. Holds identity, status, referral code, and a **cached** copy of balances (authoritative balances live in `wallets`/`wallet_transactions`).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | Surrogate PK. |
| uuid | CHAR(36) | No | — | Public opaque user identifier (used in APIs/logs; not the numeric id). |
| name | VARCHAR(120) | Yes | NULL | Display name. |
| email | VARCHAR(255) | Yes | NULL | Email address (may be null for pure-Google users without email scope; usually present). |
| email_verified_at | DATETIME | Yes | NULL | When email was verified; NULL = unverified. |
| phone | VARCHAR(20) | Yes | NULL | Optional phone (E.164). |
| avatar_url | VARCHAR(512) | Yes | NULL | Profile image URL/path. |
| referral_code | VARCHAR(12) | No | — | Unique code this user shares to refer others. |
| referred_by | BIGINT UNSIGNED | Yes | NULL | FK → users.id of the referrer (self-reference). |
| coin_balance_cache | BIGINT UNSIGNED | No | 0 | Denormalized coin balance for fast reads; reconciled from ledger. |
| cash_balance_cache | DECIMAL(18,4) UNSIGNED | No | 0.0000 | Denormalized withdrawable cash-equivalent. |
| status | ENUM('active','suspended','banned','deleted') | No | 'active' | Account lifecycle state. |
| country_code | CHAR(2) | Yes | NULL | ISO-3166 alpha-2 (targeting, offers). |
| locale | VARCHAR(10) | Yes | NULL | Preferred language (e.g., en, hi). |
| last_login_at | DATETIME | Yes | NULL | Last successful authentication. |
| registration_ip | VARCHAR(45) | Yes | NULL | IP at signup (fraud). |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |
| deleted_at | DATETIME | Yes | NULL | Soft-delete marker (GDPR account deletion). |

**3. Primary Key** — `id`.

**4. Foreign Keys** — `referred_by` → `users.id` (self), `ON DELETE SET NULL`, `ON UPDATE CASCADE`.

**5. Indexes** — `idx_users_referred_by (referred_by)`; `idx_users_status (status)`; `idx_users_created_at (created_at)`; `idx_users_country (country_code)`.

**6. Unique Constraints** — `uq_users_uuid (uuid)`; `uq_users_email (email)` (allows multiple NULLs in MySQL); `uq_users_referral_code (referral_code)`.

**7. Relationships** — 1—1 with `wallets`, `user_kyc`, `user_settings`; 1—N with virtually every user-owned table; self-referential N—1 via `referred_by`.

**8. Validation Rules** — `email` RFC-valid and lowercased before store; `referral_code` uppercase alphanumeric, length 6–12, collision-checked on generation; `status` transitions controlled server-side; balances never written directly by clients.

**9. Notes** — Balance cache columns are convenience only; a scheduled reconciliation job compares them to the ledger sum and flags drift. `deleted_users` are anonymized (PII nulled) rather than hard-deleted to preserve ledger/audit integrity.

---

## A.2 `user_auth_providers`

**1. Table Purpose** — Links a user to one or more authentication methods (Google, email/password), storing provider identifiers and password hash for email login.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| provider | ENUM('google','email') | No | — | Auth method. |
| provider_uid | VARCHAR(191) | Yes | NULL | Provider's stable user id (Google `sub`); NULL for email provider. |
| password_hash | VARCHAR(255) | Yes | NULL | bcrypt/argon2 hash (email provider only). |
| email | VARCHAR(255) | Yes | NULL | Email tied to this provider identity. |
| is_primary | TINYINT(1) UNSIGNED | No | 0 | Marks the primary login method. |
| last_used_at | DATETIME | Yes | NULL | Last authentication via this provider. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.

**4. Foreign Keys** — `user_id` → `users.id`, `ON DELETE CASCADE`.

**5. Indexes** — `idx_uap_user (user_id)`.

**6. Unique Constraints** — `uq_uap_provider_uid (provider, provider_uid)`; `uq_uap_user_provider (user_id, provider)` (one identity per provider per user).

**7. Relationships** — N—1 `users`.

**8. Validation Rules** — `password_hash` required when `provider='email'`, must be NULL otherwise; `provider_uid` required when `provider='google'`. Never store plaintext passwords. Hashes never leave the server.

**9. Notes** — Supports account linking (same user, multiple providers). Password reset updates `password_hash` and invalidates sessions.

---

## A.3 `user_devices`

**1. Table Purpose** — Registered devices per user, holding the **FCM push token** and fraud fingerprint signals.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| device_uuid | VARCHAR(191) | No | — | Client-generated stable device identifier. |
| fcm_token | VARCHAR(255) | Yes | NULL | Firebase Cloud Messaging registration token. |
| platform | ENUM('android','ios','web') | No | 'android' | Device platform. |
| device_model | VARCHAR(120) | Yes | NULL | Hardware model string. |
| os_version | VARCHAR(40) | Yes | NULL | OS version. |
| app_version | VARCHAR(20) | Yes | NULL | Installed app version. |
| fingerprint_hash | VARCHAR(191) | Yes | NULL | Hashed composite device fingerprint (fraud). |
| is_emulator | TINYINT(1) UNSIGNED | No | 0 | Emulator detection flag. |
| is_rooted | TINYINT(1) UNSIGNED | No | 0 | Root/jailbreak flag. |
| last_ip | VARCHAR(45) | Yes | NULL | Last seen IP. |
| last_seen_at | DATETIME | Yes | NULL | Last activity from this device. |
| push_enabled | TINYINT(1) UNSIGNED | No | 1 | Whether push is enabled on this device. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.

**4. Foreign Keys** — `user_id` → `users.id`, `ON DELETE CASCADE`.

**5. Indexes** — `idx_ud_user (user_id)`; `idx_ud_fcm (fcm_token)`; `idx_ud_fingerprint (fingerprint_hash)` (multi-account-per-device detection); `idx_ud_last_seen (last_seen_at)`.

**6. Unique Constraints** — `uq_ud_user_device (user_id, device_uuid)`.

**7. Relationships** — N—1 `users`.

**8. Validation Rules** — Stale/invalid FCM tokens pruned when FCM returns `UNREGISTERED`. `fingerprint_hash` shared across many users triggers fraud review.

**9. Notes** — A single `fcm_token` should map to one active user; on re-login the token is re-assigned. High-cardinality; consider archival of long-inactive devices.

---

## A.4 `user_sessions`

**1. Table Purpose** — Tracks issued refresh-token sessions to enable revocation, device logout, and "logout everywhere".

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| device_id | BIGINT UNSIGNED | Yes | NULL | FK → user_devices.id. |
| refresh_token_hash | CHAR(64) | No | — | SHA-256 hash of the refresh token (never store raw). |
| jwt_id | CHAR(36) | Yes | NULL | Last issued access-token jti (optional correlation). |
| ip_address | VARCHAR(45) | Yes | NULL | IP at issue. |
| user_agent | VARCHAR(255) | Yes | NULL | Client UA. |
| expires_at | DATETIME | No | — | Refresh token expiry. |
| revoked_at | DATETIME | Yes | NULL | When revoked; NULL = active. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.

**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE CASCADE`; `device_id` → `user_devices.id` `ON DELETE SET NULL`.

**5. Indexes** — `idx_us_user (user_id)`; `idx_us_expires (expires_at)`; `idx_us_revoked (revoked_at)`.

**6. Unique Constraints** — `uq_us_refresh_hash (refresh_token_hash)`.

**7. Relationships** — N—1 `users`; N—1 `user_devices`.

**8. Validation Rules** — Refresh tokens rotated on use (old row revoked, new inserted). Expired/revoked sessions rejected. Cleanup job deletes rows past `expires_at + grace`.

**9. Notes** — Enables the §8 security requirement of revocable refresh tokens. Storing only the hash means a DB leak does not expose live tokens.

---

## A.5 `user_kyc`

**1. Table Purpose** — Optional identity/payment verification required before (or above a threshold of) withdrawals.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id (one-to-one). |
| full_name | VARCHAR(150) | Yes | NULL | Legal name. |
| document_type | ENUM('aadhaar','pan','passport','driving_license','other') | Yes | NULL | ID document type. |
| document_number_enc | VARBINARY(512) | Yes | NULL | Encrypted document number (app-layer encryption). |
| document_file_url | VARCHAR(512) | Yes | NULL | Secure path to uploaded document. |
| status | ENUM('pending','submitted','approved','rejected') | No | 'pending' | Verification state. |
| rejection_reason | VARCHAR(255) | Yes | NULL | Reason if rejected. |
| reviewed_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id. |
| reviewed_at | DATETIME | Yes | NULL | Review timestamp. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.

**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE CASCADE`; `reviewed_by` → `admins.id` `ON DELETE SET NULL`.

**5. Indexes** — `idx_kyc_status (status)`.

**6. Unique Constraints** — `uq_kyc_user (user_id)` (enforces 1—1).

**7. Relationships** — 1—1 `users`; N—1 `admins` (reviewer).

**8. Validation Rules** — Document number stored **encrypted at rest**; never returned to clients in full (masked). Status machine: pending → submitted → approved/rejected.

**9. Notes** — PII-sensitive; access restricted and audited. Encryption key managed outside the DB (env/KMS).

---

## A.6 `user_settings`

**1. Table Purpose** — Per-user preferences (notification toggles, language, theme mode).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id (1—1). |
| notif_push_enabled | TINYINT(1) UNSIGNED | No | 1 | Master push toggle. |
| notif_transactional | TINYINT(1) UNSIGNED | No | 1 | Transactional push toggle. |
| notif_promotional | TINYINT(1) UNSIGNED | No | 1 | Promotional push toggle. |
| language | VARCHAR(10) | No | 'en' | UI language. |
| theme_mode | ENUM('system','light','dark') | No | 'system' | Preferred theme mode. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE CASCADE`.
**5. Indexes** — (covered by unique below).
**6. Unique Constraints** — `uq_usettings_user (user_id)`.
**7. Relationships** — 1—1 `users`.
**8. Validation Rules** — Promotional pushes suppressed when `notif_promotional=0`; master toggle overrides all.
**9. Notes** — Created lazily on first settings write or eagerly at signup with defaults.

---

# B. Wallet & Currency

> **The financial core.** `wallets` holds current balances; `wallet_transactions` is the immutable ledger and single source of truth. Every credit/debit anywhere in the system writes exactly one ledger row inside a transaction that locks the wallet row.

## B.1 `wallets`

**1. Table Purpose** — Current coin and cash-equivalent balances per user, plus reserved (held) amounts for pending withdrawals. Ledger-backed and reconciled.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id (1—1). |
| coin_balance | BIGINT UNSIGNED | No | 0 | Spendable coin balance. |
| coin_reserved | BIGINT UNSIGNED | No | 0 | Coins held against pending withdrawals. |
| lifetime_coins_earned | BIGINT UNSIGNED | No | 0 | Cumulative coins ever credited (stats/leaderboard). |
| lifetime_coins_spent | BIGINT UNSIGNED | No | 0 | Cumulative coins ever debited. |
| cash_balance | DECIMAL(18,4) UNSIGNED | No | 0.0000 | Withdrawable cash-equivalent (if tracked separately). |
| cash_reserved | DECIMAL(18,4) UNSIGNED | No | 0.0000 | Cash held against pending withdrawals. |
| version | BIGINT UNSIGNED | No | 0 | Optimistic-lock / write counter (increments each mutation). |
| last_transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id of the most recent ledger row. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.

**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE RESTRICT` (a user with a wallet is anonymized, not deleted); `last_transaction_id` → `wallet_transactions.id` `ON DELETE SET NULL`.

**5. Indexes** — `idx_wallets_last_txn (last_transaction_id)`.

**6. Unique Constraints** — `uq_wallets_user (user_id)`.

**7. Relationships** — 1—1 `users`; 1—N `wallet_transactions`.

**8. Validation Rules** — `coin_balance >= 0` and `coin_reserved >= 0` (enforced by using UNSIGNED + application guards; MySQL 8 `CHECK` may reinforce). Available = `coin_balance` (reserved already deducted on hold). All mutations wrapped in `SELECT ... FOR UPDATE`. Balance never edited without a corresponding ledger row.

**9. Notes** — `version` supports optimistic concurrency where row-locking is undesirable. Reconciliation job asserts `coin_balance == SUM(ledger deltas)` per user and raises a `fraud_flags`/ops alert on mismatch.

---

## B.2 `wallet_transactions` (Ledger) ⭐

**1. Table Purpose** — The **immutable, append-only financial ledger**. Every coin/cash movement (earn, spend, withdrawal hold/release, referral commission, manual admin adjustment, reversal) is recorded as exactly one row. Balances are derivable from and reconciled against this table. Idempotency is enforced here.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK (also chronological order). |
| uuid | CHAR(36) | No | — | Public transaction identifier for APIs. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id (owner). |
| wallet_id | BIGINT UNSIGNED | No | — | FK → wallets.id. |
| direction | ENUM('credit','debit') | No | — | Whether coins increased or decreased. |
| amount | BIGINT UNSIGNED | No | — | Magnitude of coins moved (always positive; sign implied by `direction`). |
| cash_amount | DECIMAL(18,4) UNSIGNED | Yes | NULL | Cash value moved, when applicable (withdrawals). |
| balance_after | BIGINT UNSIGNED | No | — | Coin balance immediately after this row (audit/trace). |
| type | ENUM('checkin','scratch','spin','task','offerwall','cpa','referral','referral_commission','withdrawal_hold','withdrawal_release','withdrawal_debit','rewarded_ad','admin_credit','admin_debit','reversal','adjustment') | No | — | Business category of the movement. |
| source_module | VARCHAR(40) | No | — | Originating module (checkin, offerwall, withdraw, admin, …). |
| source_id | BIGINT UNSIGNED | Yes | NULL | PK of the originating row (e.g., offer_conversions.id, withdraw_requests.id). |
| reference_id | VARCHAR(191) | No | — | **Idempotency key** — globally unique per logical event (e.g., `offerwall:<provider>:<txn_id>`). |
| related_transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id (links a reversal to its original). |
| performed_by_admin_id | BIGINT UNSIGNED | Yes | NULL | FK → admins.id for manual adjustments. |
| description | VARCHAR(255) | Yes | NULL | Human-readable memo. |
| metadata | JSON | Yes | NULL | Structured context (offer name, campaign, etc.). |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Ledger timestamp (UTC). |

**3. Primary Key** — `id`.

**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE RESTRICT`; `wallet_id` → `wallets.id` `ON DELETE RESTRICT`; `related_transaction_id` → `wallet_transactions.id` `ON DELETE SET NULL`; `performed_by_admin_id` → `admins.id` `ON DELETE SET NULL`.

**5. Indexes** — `idx_wt_user_created (user_id, created_at)` (history pagination — primary read path); `idx_wt_wallet (wallet_id)`; `idx_wt_type (type)`; `idx_wt_source (source_module, source_id)`; `idx_wt_related (related_transaction_id)`; `idx_wt_created (created_at)` (reporting/partition key).

**6. Unique Constraints** — `uq_wt_reference (reference_id)` — the linchpin preventing double-credit from retries/duplicate postbacks; `uq_wt_uuid (uuid)`.

**7. Relationships** — N—1 `users`; N—1 `wallets`; self-reference for reversals; N—1 `admins` (optional).

**8. Validation Rules** — **Immutable**: no `UPDATE`/`DELETE` permitted (corrections are made by inserting a `reversal` row referencing the original). `amount > 0`. `balance_after` must equal prior balance ± amount. Every insert happens inside the same transaction that updates `wallets` under a row lock. `reference_id` must be deterministically derivable from the source event.

**9. Notes** — The most write- and read-heavy financial table; **partition by `created_at` (monthly RANGE)** and archive cold partitions as volume grows (§12). No soft delete — the ledger is permanent. Reporting aggregates should read from replicas/summary tables, not this table directly, at scale.

---

## B.3 `currency_settings`

**1. Table Purpose** — Global coin↔cash conversion configuration and withdrawal thresholds. Typically a single active row (versioned).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| coin_to_cash_rate | DECIMAL(18,8) | No | — | Cash value of one coin (e.g., 0.00100000). |
| currency_code | CHAR(3) | No | 'INR' | ISO-4217 payout currency. |
| min_withdraw_coins | BIGINT UNSIGNED | No | — | Minimum coins required to withdraw. |
| max_withdraw_coins | BIGINT UNSIGNED | Yes | NULL | Optional per-request cap. |
| daily_earn_cap_coins | BIGINT UNSIGNED | Yes | NULL | Optional per-user daily earn ceiling (fraud). |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Whether this settings row is the live one. |
| effective_from | DATETIME | Yes | NULL | When this configuration takes effect. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_cs_active (is_active)`.
**6. Unique Constraints** — partial-unique intent: only one `is_active=1` row (enforced in application; MySQL lacks partial unique — a generated column trick or app guard is used).
**7. Relationships** — Referenced globally by wallet/withdraw services (no hard FK; read as config).
**8. Validation Rules** — `coin_to_cash_rate > 0`; `min_withdraw_coins > 0`; rate changes are additive rows (history retained), not in-place edits, for auditability.
**9. Notes** — Kept as rows (not just `app_settings`) so rate history is preserved for reconciling historical withdrawals.

---

# C. Reward Mechanics

## C.1 `daily_checkins`

**1. Table Purpose** — Records each user's daily check-in claim and current streak state.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| checkin_date | DATE | No | — | The calendar day claimed (UTC). |
| streak_day | SMALLINT UNSIGNED | No | 1 | Position in the current streak (1..N). |
| coins_awarded | BIGINT UNSIGNED | No | 0 | Coins granted for this check-in. |
| transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id (the credit). |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Claim timestamp. |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE CASCADE`; `transaction_id` → `wallet_transactions.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_dc_user (user_id)`.
**6. Unique Constraints** — `uq_dc_user_date (user_id, checkin_date)` — **one check-in per user per day** (prevents double-claim).
**7. Relationships** — N—1 `users`; 1—1 with a ledger row.
**8. Validation Rules** — Claim allowed once per UTC day; streak resets if the previous day was missed (computed server-side from last check-in). `coins_awarded` derived from `checkin_rewards_config` for `streak_day`.
**9. Notes** — No `updated_at` (append-only). Reward ladder is config-driven.

---

## C.2 `checkin_rewards_config`

**1. Table Purpose** — The day-wise reward ladder for daily check-ins (e.g., day 1 → 10 coins, day 7 → 100 coins).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| day_number | SMALLINT UNSIGNED | No | — | Streak day this reward applies to. |
| coins | BIGINT UNSIGNED | No | — | Coins awarded on that day. |
| is_milestone | TINYINT(1) UNSIGNED | No | 0 | Marks bonus/milestone days. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Whether this rung is active. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_crc_active (is_active)`.
**6. Unique Constraints** — `uq_crc_day (day_number)`.
**7. Relationships** — Referenced by `CheckinService`; no hard FK to `daily_checkins`.
**8. Validation Rules** — `day_number >= 1`; `coins >= 0`. After the max configured day, streak either caps or cycles (business rule).
**9. Notes** — Admin-editable via Reward Management; changes audited.

---

## C.3 `scratch_cards`

**1. Table Purpose** — Scratch cards issued to users, their (server-decided) reward, and reveal/claim lifecycle.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| source | ENUM('daily','task','offer','purchase','admin') | No | 'daily' | How the card was issued. |
| reward_coins | BIGINT UNSIGNED | Yes | NULL | Reward value (decided at reveal or issue). |
| status | ENUM('issued','revealed','claimed','expired') | No | 'issued' | Card lifecycle. |
| revealed_at | DATETIME | Yes | NULL | When revealed. |
| claimed_at | DATETIME | Yes | NULL | When reward credited. |
| expires_at | DATETIME | Yes | NULL | Expiry deadline. |
| transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id (credit on claim). |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Issue time. |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE CASCADE`; `transaction_id` → `wallet_transactions.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_sc_user_status (user_id, status)`; `idx_sc_expires (expires_at)` (expiry sweep job).
**6. Unique Constraints** — none beyond PK.
**7. Relationships** — N—1 `users`; 1—1 ledger row on claim.
**8. Validation Rules** — Reward decided **server-side** (weighted from `scratch_card_config`) at reveal; status machine issued → revealed → claimed, or → expired. Cannot claim an expired/already-claimed card.
**9. Notes** — Expiry sweep is a cron job (§1.4). Reward assignment at reveal (not issue) reduces the value of DB scraping.

---

## C.4 `scratch_card_config`

**1. Table Purpose** — Prize pool and probability weights for scratch card rewards.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| label | VARCHAR(80) | No | — | Prize tier label. |
| reward_coins | BIGINT UNSIGNED | No | — | Coins for this tier. |
| weight | INT UNSIGNED | No | — | Relative probability weight. |
| daily_limit | INT UNSIGNED | Yes | NULL | Max times this prize can be won per user/day. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Active in the pool. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_scc_active (is_active)`.
**6. Unique Constraints** — none.
**7. Relationships** — Referenced by `ScratchService`.
**8. Validation Rules** — `weight > 0`; sum of active weights defines the distribution. Probabilities never exposed to clients.
**9. Notes** — Admin-tunable odds; changes audited. High-value tiers should carry small weights and per-day caps.

---

## C.5 `spin_wheel_segments`

**1. Table Purpose** — Definitions of the spin wheel's segments (reward + odds + display).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| label | VARCHAR(80) | No | — | Segment display label. |
| reward_type | ENUM('coins','scratch_card','nothing','bonus') | No | 'coins' | What the segment grants. |
| reward_coins | BIGINT UNSIGNED | No | 0 | Coins if reward_type='coins'. |
| weight | INT UNSIGNED | No | — | Relative probability weight. |
| color_hex | CHAR(7) | Yes | NULL | UI color (#RRGGBB). |
| position | SMALLINT UNSIGNED | No | 0 | Display order around the wheel. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Whether the segment is live. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_sws_active (is_active)`.
**6. Unique Constraints** — `uq_sws_position (position)`.
**7. Relationships** — 1—N `spin_history`.
**8. Validation Rules** — `weight > 0`; outcome selected **server-side** by weighted random. Client animation must land on the server-chosen segment.
**9. Notes** — Admin-managed odds; audited.

---

## C.6 `spin_history`

**1. Table Purpose** — Records each spin outcome and enforces per-user daily spin limits.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| segment_id | BIGINT UNSIGNED | No | — | FK → spin_wheel_segments.id (result). |
| reward_coins | BIGINT UNSIGNED | No | 0 | Coins awarded. |
| spin_date | DATE | No | — | Day of spin (limit tracking). |
| source | ENUM('free','ad','purchase') | No | 'free' | How the spin was granted. |
| transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Spin time. |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE CASCADE`; `segment_id` → `spin_wheel_segments.id` `ON DELETE RESTRICT`; `transaction_id` → `wallet_transactions.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_sh_user_date (user_id, spin_date)` (daily-limit check); `idx_sh_segment (segment_id)`.
**6. Unique Constraints** — none (multiple spins/day allowed up to a limit).
**7. Relationships** — N—1 `users`; N—1 `spin_wheel_segments`; 1—1 ledger row.
**8. Validation Rules** — Daily spin count enforced against `spin_date` + configured limit before granting. Append-only.
**9. Notes** — `idx_sh_user_date` also serves history views.

---

## C.7 `tasks`

**1. Table Purpose** — Catalog of tasks users can complete for rewards (follow, watch, install, survey, etc.).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| title | VARCHAR(160) | No | — | Task title. |
| description | TEXT | Yes | NULL | Instructions. |
| task_type | ENUM('social','video','install','survey','daily','custom') | No | 'custom' | Category. |
| reward_coins | BIGINT UNSIGNED | No | — | Coins on completion. |
| action_url | VARCHAR(512) | Yes | NULL | Target/deep link. |
| verification_type | ENUM('auto','manual','callback') | No | 'manual' | How completion is verified. |
| max_completions | INT UNSIGNED | Yes | NULL | Global completion cap (NULL = unlimited). |
| per_user_limit | INT UNSIGNED | No | 1 | How many times a single user may complete. |
| icon_url | VARCHAR(512) | Yes | NULL | Display icon. |
| starts_at | DATETIME | Yes | NULL | Availability start. |
| ends_at | DATETIME | Yes | NULL | Availability end. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Whether shown/active. |
| sort_order | INT UNSIGNED | No | 0 | Display order. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_tasks_active_window (is_active, starts_at, ends_at)`; `idx_tasks_type (task_type)`; `idx_tasks_sort (sort_order)`.
**6. Unique Constraints** — none.
**7. Relationships** — 1—N `task_completions`.
**8. Validation Rules** — `reward_coins >= 0`; `per_user_limit >= 1`; window valid (`ends_at > starts_at`). Only active, in-window tasks are served.
**9. Notes** — Admin-managed (Reward Management). Supports timed campaigns via the window columns.

---

## C.8 `task_completions`

**1. Table Purpose** — Tracks which user completed which task, verification state, and reward crediting.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| task_id | BIGINT UNSIGNED | No | — | FK → tasks.id. |
| status | ENUM('started','pending','approved','rejected','credited') | No | 'started' | Completion state. |
| reward_coins | BIGINT UNSIGNED | No | 0 | Coins granted (snapshot at completion). |
| proof_url | VARCHAR(512) | Yes | NULL | Optional proof (screenshot). |
| verification_ref | VARCHAR(191) | Yes | NULL | External verification/callback reference. |
| transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id (credit). |
| reviewed_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id (manual verification). |
| completed_at | DATETIME | Yes | NULL | When marked complete. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Start time. |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE CASCADE`; `task_id` → `tasks.id` `ON DELETE RESTRICT`; `transaction_id` → `wallet_transactions.id` `ON DELETE SET NULL`; `reviewed_by` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_tc_user (user_id)`; `idx_tc_task (task_id)`; `idx_tc_status (status)`.
**6. Unique Constraints** — `uq_tc_user_task_ref (user_id, task_id, verification_ref)` enforces idempotency for callback tasks; per-user-limit enforced in application against count.
**7. Relationships** — N—1 `users`; N—1 `tasks`; 1—1 ledger row on credit; N—1 `admins`.
**8. Validation Rules** — Credit only once (guarded by ledger `reference_id` = `task:<completion_id>`). Respect `per_user_limit`/`max_completions`. State machine enforced server-side.
**9. Notes** — Manual tasks appear in an admin review queue via `status='pending'`.

---

# D. Offerwall & CPA

## D.1 `offerwall_providers`

**1. Table Purpose** — Configured offerwall/ad networks (name, credentials, postback secret, IP allowlist, active flag).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| name | VARCHAR(80) | No | — | Provider name (e.g., AdGate, OfferToro). |
| slug | VARCHAR(60) | No | — | URL-safe identifier used in postback routes. |
| api_key_enc | VARBINARY(512) | Yes | NULL | Encrypted API key. |
| postback_secret_enc | VARBINARY(512) | Yes | NULL | Encrypted secret for signature verification. |
| ip_allowlist | JSON | Yes | NULL | Array of allowed postback source IPs/CIDRs. |
| currency_ratio | DECIMAL(18,6) | No | 1.000000 | Provider-currency → coins multiplier. |
| logo_url | VARCHAR(512) | Yes | NULL | Display logo. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Whether enabled. |
| sort_order | INT UNSIGNED | No | 0 | Display order. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_op_active (is_active)`.
**6. Unique Constraints** — `uq_op_slug (slug)`.
**7. Relationships** — 1—N `offers`, `offer_conversions`, `cpa_offers`, `postback_logs`.
**8. Validation Rules** — Secrets stored **encrypted**, write-only in admin UI. `slug` immutable once postbacks are live. `ip_allowlist` enforced on inbound postbacks.
**9. Notes** — Enabling/disabling a provider hides its offers instantly (config-driven).

---

## D.2 `offers`

**1. Table Purpose** — Curated/cached offers surfaced to users (title, payout, category, provider).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| provider_id | BIGINT UNSIGNED | No | — | FK → offerwall_providers.id. |
| external_offer_id | VARCHAR(191) | Yes | NULL | Provider's offer id. |
| title | VARCHAR(200) | No | — | Offer title. |
| description | TEXT | Yes | NULL | Details/requirements. |
| category | VARCHAR(60) | Yes | NULL | Offer category. |
| payout_coins | BIGINT UNSIGNED | No | — | Coins the user earns. |
| provider_payout | DECIMAL(18,4) | Yes | NULL | Revenue paid to platform (margin analysis). |
| tracking_url | VARCHAR(1024) | Yes | NULL | Click/redirect URL template. |
| icon_url | VARCHAR(512) | Yes | NULL | Offer image. |
| countries | JSON | Yes | NULL | Geo-targeting list. |
| platforms | JSON | Yes | NULL | Device targeting. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Whether shown. |
| sort_order | INT UNSIGNED | No | 0 | Display order. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `provider_id` → `offerwall_providers.id` `ON DELETE CASCADE`.
**5. Indexes** — `idx_offers_provider (provider_id)`; `idx_offers_active (is_active)`; `idx_offers_category (category)`.
**6. Unique Constraints** — `uq_offers_provider_ext (provider_id, external_offer_id)`.
**7. Relationships** — N—1 `offerwall_providers`; 1—N `offer_clicks`.
**8. Validation Rules** — `payout_coins >= 0`. Geo/platform filters applied at query time.
**9. Notes** — May be refreshed from provider APIs on a schedule; `external_offer_id` supports upserts.

---

## D.3 `offer_clicks`

**1. Table Purpose** — Records user clicks/opens for attribution and fraud velocity checks.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| offer_id | BIGINT UNSIGNED | Yes | NULL | FK → offers.id. |
| provider_id | BIGINT UNSIGNED | No | — | FK → offerwall_providers.id. |
| click_token | CHAR(36) | No | — | Unique token echoed back in postback for attribution. |
| ip_address | VARCHAR(45) | Yes | NULL | Click IP. |
| device_id | BIGINT UNSIGNED | Yes | NULL | FK → user_devices.id. |
| user_agent | VARCHAR(255) | Yes | NULL | Client UA. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Click time. |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE CASCADE`; `offer_id` → `offers.id` `ON DELETE SET NULL`; `provider_id` → `offerwall_providers.id` `ON DELETE CASCADE`; `device_id` → `user_devices.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_oc_user_created (user_id, created_at)`; `idx_oc_provider (provider_id)`.
**6. Unique Constraints** — `uq_oc_click_token (click_token)`.
**7. Relationships** — N—1 `users`, `offers`, `offerwall_providers`, `user_devices`.
**8. Validation Rules** — `click_token` is the attribution join to `offer_conversions`. Velocity (clicks/min per user/IP) monitored by fraud engine.
**9. Notes** — High-volume; partition/archival candidate.

---

## D.4 `offer_conversions` ⭐

**1. Table Purpose** — Confirmed conversions from provider **postbacks** — the trigger for crediting coins. Idempotent by `(provider_id, transaction_id)`.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| provider_id | BIGINT UNSIGNED | No | — | FK → offerwall_providers.id. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id (resolved via click_token). |
| offer_id | BIGINT UNSIGNED | Yes | NULL | FK → offers.id. |
| click_id | BIGINT UNSIGNED | Yes | NULL | FK → offer_clicks.id (attribution). |
| transaction_id_ext | VARCHAR(191) | No | — | Provider's unique conversion/transaction id. |
| payout_coins | BIGINT UNSIGNED | No | — | Coins credited to the user. |
| provider_revenue | DECIMAL(18,4) | Yes | NULL | Revenue reported by provider. |
| status | ENUM('pending','credited','reversed','rejected') | No | 'pending' | Conversion state (supports chargebacks). |
| wallet_transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id (the credit). |
| ip_address | VARCHAR(45) | Yes | NULL | Postback source IP. |
| signature_valid | TINYINT(1) UNSIGNED | No | 0 | Whether the signature/IP check passed. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Postback receipt time. |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `provider_id` → `offerwall_providers.id` `ON DELETE RESTRICT`; `user_id` → `users.id` `ON DELETE RESTRICT`; `offer_id` → `offers.id` `ON DELETE SET NULL`; `click_id` → `offer_clicks.id` `ON DELETE SET NULL`; `wallet_transaction_id` → `wallet_transactions.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_ocv_user_created (user_id, created_at)`; `idx_ocv_status (status)`; `idx_ocv_provider (provider_id)`.
**6. Unique Constraints** — `uq_ocv_provider_txn (provider_id, transaction_id_ext)` — **the idempotency guarantee** (duplicate/replayed postbacks rejected).
**7. Relationships** — N—1 `offerwall_providers`, `users`, `offers`, `offer_clicks`; 1—1 ledger row.
**8. Validation Rules** — Credit only when `signature_valid=1` and payout matches configured offer. `reversed` status triggers a compensating `reversal` ledger row. Never credited twice.
**9. Notes** — Chargebacks/holds handled by status transitions + ledger reversals; supports negative-balance handling per policy.

---

## D.5 `cpa_offers`

**1. Table Purpose** — CPA-specific offer definitions and payout tiers (distinct from generic offerwall offers).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| provider_id | BIGINT UNSIGNED | No | — | FK → offerwall_providers.id. |
| external_offer_id | VARCHAR(191) | Yes | NULL | Provider offer id. |
| title | VARCHAR(200) | No | — | Offer title. |
| goal | VARCHAR(160) | Yes | NULL | Conversion goal (install, signup, deposit). |
| payout_coins | BIGINT UNSIGNED | No | — | User reward. |
| payout_tiers | JSON | Yes | NULL | Multi-goal/tiered payouts. |
| countries | JSON | Yes | NULL | Geo targeting. |
| tracking_url | VARCHAR(1024) | Yes | NULL | Redirect URL. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Active flag. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `provider_id` → `offerwall_providers.id` `ON DELETE CASCADE`.
**5. Indexes** — `idx_cpa_provider (provider_id)`; `idx_cpa_active (is_active)`.
**6. Unique Constraints** — `uq_cpa_provider_ext (provider_id, external_offer_id)`.
**7. Relationships** — N—1 `offerwall_providers`; conversions recorded in `offer_conversions` (shared pipeline).
**8. Validation Rules** — `payout_coins >= 0`; tiered payouts validated as ascending goals.
**9. Notes** — Reuses the `offer_conversions` postback pipeline for crediting.

---

## D.6 `postback_logs`

**1. Table Purpose** — Raw audit of every inbound postback (payload, IP, signature result) — forensic + debugging + replay defense.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| provider_id | BIGINT UNSIGNED | Yes | NULL | FK → offerwall_providers.id (NULL if unrecognized). |
| endpoint | VARCHAR(120) | No | — | Route hit (offerwall/cpa + slug). |
| http_method | VARCHAR(8) | No | 'GET' | Request method. |
| raw_payload | JSON | Yes | NULL | Full query/body payload. |
| ip_address | VARCHAR(45) | Yes | NULL | Source IP. |
| signature_valid | TINYINT(1) UNSIGNED | No | 0 | Signature verification result. |
| ip_allowed | TINYINT(1) UNSIGNED | No | 0 | IP-allowlist result. |
| processing_result | ENUM('credited','duplicate','invalid_signature','ip_blocked','user_not_found','error') | No | 'error' | Outcome. |
| conversion_id | BIGINT UNSIGNED | Yes | NULL | FK → offer_conversions.id (if created). |
| error_message | VARCHAR(512) | Yes | NULL | Failure detail. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Receipt time. |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `provider_id` → `offerwall_providers.id` `ON DELETE SET NULL`; `conversion_id` → `offer_conversions.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_pl_provider_created (provider_id, created_at)`; `idx_pl_result (processing_result)`; `idx_pl_created (created_at)`.
**6. Unique Constraints** — none (logs everything, including rejected/duplicate).
**7. Relationships** — N—1 `offerwall_providers`; N—1 `offer_conversions`.
**8. Validation Rules** — Append-only; never trusted as a credit source (credit lives in `offer_conversions`). Retention-limited.
**9. Notes** — Very high volume; **partition by `created_at`**, short retention (e.g., 90 days) then archive.

---

# E. Referral

## E.1 `referrals`

**1. Table Purpose** — Links a referrer to a referee and tracks qualification and reward status.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| referrer_id | BIGINT UNSIGNED | No | — | FK → users.id (who invited). |
| referee_id | BIGINT UNSIGNED | No | — | FK → users.id (who joined). |
| referral_code | VARCHAR(12) | No | — | Code used at signup (snapshot). |
| status | ENUM('pending','qualified','rewarded','rejected') | No | 'pending' | Lifecycle (qualifies on referee action). |
| signup_bonus_coins | BIGINT UNSIGNED | No | 0 | One-time bonus granted to referrer. |
| referee_bonus_coins | BIGINT UNSIGNED | No | 0 | One-time bonus granted to referee. |
| qualified_at | DATETIME | Yes | NULL | When qualification criteria met. |
| rewarded_at | DATETIME | Yes | NULL | When bonuses credited. |
| referrer_transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id (referrer bonus). |
| referee_transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id (referee bonus). |
| signup_ip | VARCHAR(45) | Yes | NULL | Referee signup IP (self-referral fraud). |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `referrer_id` → `users.id` `ON DELETE RESTRICT`; `referee_id` → `users.id` `ON DELETE RESTRICT`; both transaction FKs → `wallet_transactions.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_ref_referrer (referrer_id)`; `idx_ref_status (status)`.
**6. Unique Constraints** — `uq_ref_referee (referee_id)` — **a user can only be referred once**.
**7. Relationships** — N—1 `users` (twice: referrer + referee); 1—N `referral_earnings`.
**8. Validation Rules** — `referrer_id != referee_id` (no self-referral). Same-device/IP referrer↔referee flagged. Bonuses credited once (ledger `reference_id` guards).
**9. Notes** — Qualification rule (e.g., referee completes first earn) is config-driven via `referral_config`.

---

## E.2 `referral_config`

**1. Table Purpose** — Configuration of referral rewards, qualification rules, and commission rate.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| referrer_bonus_coins | BIGINT UNSIGNED | No | 0 | One-time referrer bonus. |
| referee_bonus_coins | BIGINT UNSIGNED | No | 0 | One-time referee bonus. |
| commission_percent | DECIMAL(6,4) | No | 0.0000 | % of referee earnings paid to referrer. |
| qualification_rule | ENUM('on_signup','on_first_earn','on_first_withdraw') | No | 'on_first_earn' | When a referral qualifies. |
| commission_duration_days | INT UNSIGNED | Yes | NULL | How long commission accrues (NULL = lifetime). |
| max_referrals_per_user | INT UNSIGNED | Yes | NULL | Cap on rewarded referrals. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Live config. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_rc_active (is_active)`.
**6. Unique Constraints** — one active row (app-enforced).
**7. Relationships** — Referenced by `ReferralService`.
**8. Validation Rules** — `commission_percent` between 0 and 1; changes retained as history rows.
**9. Notes** — Admin-managed (Referral Management); audited.

---

## E.3 `referral_earnings`

**1. Table Purpose** — Commission entries generated to the referrer from a referee's earning activity.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| referral_id | BIGINT UNSIGNED | No | — | FK → referrals.id. |
| referrer_id | BIGINT UNSIGNED | No | — | FK → users.id (earner of commission). |
| referee_id | BIGINT UNSIGNED | No | — | FK → users.id (source of activity). |
| source_transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id (referee's earning). |
| commission_coins | BIGINT UNSIGNED | No | — | Commission credited. |
| transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id (the commission credit). |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `referral_id` → `referrals.id` `ON DELETE CASCADE`; `referrer_id`/`referee_id` → `users.id` `ON DELETE RESTRICT`; both transaction FKs → `wallet_transactions.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_re_referrer (referrer_id)`; `idx_re_referral (referral_id)`.
**6. Unique Constraints** — `uq_re_source_txn (source_transaction_id)` — one commission per source earning (idempotency).
**7. Relationships** — N—1 `referrals`; N—1 `users` (twice); 1—1 ledger rows.
**8. Validation Rules** — Commission = `commission_percent × source earning`, respecting `commission_duration_days`. Never double-paid.
**9. Notes** — Append-only; drives the referral earnings screen and leaderboard contributions.

---

# F. Leaderboard

## F.1 `leaderboard_periods`

**1. Table Purpose** — Defines leaderboard periods (daily/weekly/monthly/all-time) and their windows.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| period_type | ENUM('daily','weekly','monthly','all_time') | No | — | Granularity. |
| period_key | VARCHAR(20) | No | — | Canonical key (e.g., 2026-W30, 2026-07). |
| starts_at | DATETIME | No | — | Window start (UTC). |
| ends_at | DATETIME | Yes | NULL | Window end (NULL for all-time). |
| status | ENUM('active','closed') | No | 'active' | Whether still accumulating. |
| reward_config | JSON | Yes | NULL | Rank→reward mapping for period prizes. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_lp_type_status (period_type, status)`.
**6. Unique Constraints** — `uq_lp_type_key (period_type, period_key)`.
**7. Relationships** — 1—N `leaderboard_entries`.
**8. Validation Rules** — `ends_at > starts_at` when present; one active period per type.
**9. Notes** — Period rollover + prize distribution handled by a cron job at `ends_at`.

---

## F.2 `leaderboard_entries`

**1. Table Purpose** — Computed rank and score snapshot per user per period (materialized for fast reads).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| period_id | BIGINT UNSIGNED | No | — | FK → leaderboard_periods.id. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| score | BIGINT UNSIGNED | No | 0 | Accumulated score (e.g., coins earned in period). |
| rank | INT UNSIGNED | Yes | NULL | Computed rank (recomputed periodically). |
| reward_coins | BIGINT UNSIGNED | No | 0 | Prize awarded at period close. |
| rewarded | TINYINT(1) UNSIGNED | No | 0 | Whether prize credited. |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `period_id` → `leaderboard_periods.id` `ON DELETE CASCADE`; `user_id` → `users.id` `ON DELETE CASCADE`.
**5. Indexes** — `idx_le_period_rank (period_id, rank)`; `idx_le_period_score (period_id, score DESC)` (ranking sort).
**6. Unique Constraints** — `uq_le_period_user (period_id, user_id)` — one entry per user per period.
**7. Relationships** — N—1 `leaderboard_periods`; N—1 `users`.
**8. Validation Rules** — Score incremented transactionally on earn events (or recomputed in batch). Ranks recomputed by cron; ties broken by earliest `updated_at`.
**9. Notes** — At scale, back with a **Redis sorted set** for live ranking and persist snapshots here (§12).

---

# G. Withdrawals

## G.1 `withdraw_methods`

**1. Table Purpose** — Supported payout methods available to users (UPI, PayPal, gift card, bank), with per-method limits.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| name | VARCHAR(80) | No | — | Method display name. |
| code | VARCHAR(40) | No | — | Machine code (upi, paypal, amazon_gc). |
| gateway_id | BIGINT UNSIGNED | Yes | NULL | FK → payment_gateways.id (execution route). |
| min_coins | BIGINT UNSIGNED | No | — | Minimum coins to redeem via this method. |
| max_coins | BIGINT UNSIGNED | Yes | NULL | Maximum per request. |
| fee_percent | DECIMAL(6,4) | No | 0.0000 | Fee as fraction. |
| fee_flat | DECIMAL(18,4) | No | 0.0000 | Flat fee in cash. |
| detail_schema | JSON | Yes | NULL | Required payout fields (e.g., upi_id, email). |
| icon_url | VARCHAR(512) | Yes | NULL | Display icon. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Whether offered. |
| sort_order | INT UNSIGNED | No | 0 | Display order. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `gateway_id` → `payment_gateways.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_wm_active (is_active)`; `idx_wm_gateway (gateway_id)`.
**6. Unique Constraints** — `uq_wm_code (code)`.
**7. Relationships** — 1—N `withdraw_requests`; N—1 `payment_gateways`.
**8. Validation Rules** — `min_coins > 0`; `detail_schema` drives client form + server validation of `payment_detail`.
**9. Notes** — Admin-managed; disabling hides the method immediately.

---

## G.2 `withdraw_requests` ⭐

**1. Table Purpose** — User payout requests and their approval/payment lifecycle. Reserves balance atomically at creation.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| uuid | CHAR(36) | No | — | Public request reference. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| method_id | BIGINT UNSIGNED | No | — | FK → withdraw_methods.id. |
| gateway_id | BIGINT UNSIGNED | Yes | NULL | FK → payment_gateways.id (resolved route). |
| coins_amount | BIGINT UNSIGNED | No | — | Coins requested/debited. |
| cash_amount | DECIMAL(18,4) UNSIGNED | No | — | Gross cash value. |
| fee_amount | DECIMAL(18,4) UNSIGNED | No | 0.0000 | Fee deducted. |
| net_amount | DECIMAL(18,4) UNSIGNED | No | — | Cash actually paid out. |
| currency_code | CHAR(3) | No | 'INR' | Payout currency. |
| conversion_rate | DECIMAL(18,8) | No | — | Coin→cash rate snapshot at request. |
| payment_detail | JSON | No | — | Payout target (upi_id, email, etc.). |
| status | ENUM('pending','approved','processing','paid','rejected','cancelled','failed') | No | 'pending' | Lifecycle. |
| hold_transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id (reserve/hold). |
| debit_transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id (final debit on pay). |
| refund_transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id (release on reject/cancel). |
| admin_id | BIGINT UNSIGNED | Yes | NULL | FK → admins.id (processor). |
| admin_note | VARCHAR(512) | Yes | NULL | Internal note / rejection reason. |
| external_reference | VARCHAR(191) | Yes | NULL | Gateway payout reference. |
| requested_ip | VARCHAR(45) | Yes | NULL | IP at request (fraud). |
| processed_at | DATETIME | Yes | NULL | When finalized. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Request time. |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE RESTRICT`; `method_id` → `withdraw_methods.id` `ON DELETE RESTRICT`; `gateway_id` → `payment_gateways.id` `ON DELETE SET NULL`; all transaction FKs → `wallet_transactions.id` `ON DELETE SET NULL`; `admin_id` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_wr_user_created (user_id, created_at)`; `idx_wr_status (status)`; `idx_wr_gateway (gateway_id)`; `idx_wr_created (created_at)`.
**6. Unique Constraints** — `uq_wr_uuid (uuid)`.
**7. Relationships** — N—1 `users`, `withdraw_methods`, `payment_gateways`, `admins`; 1—N `withdraw_history`; 1—N `gateway_transactions`.
**8. Validation Rules** — On create: `coins_amount >= method.min_coins`, `<= available balance`, within currency thresholds; coins **reserved** via a `withdrawal_hold` ledger row + `wallets.coin_reserved` increment, all in one locked transaction. `net_amount = cash_amount - fee_amount`. Status machine strictly enforced; cancellation/rejection releases the hold via a compensating ledger row. KYC gate applied above threshold.
**9. Notes** — The hold-then-settle pattern prevents spending reserved coins. Optional dual-control for large amounts (§6.3). Conversion rate snapshotted so later rate changes don't alter in-flight requests.

---

## G.3 `withdraw_history`

**1. Table Purpose** — Immutable audit of status transitions for withdrawal requests (who/what/when).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| withdraw_request_id | BIGINT UNSIGNED | No | — | FK → withdraw_requests.id. |
| from_status | VARCHAR(20) | Yes | NULL | Previous status. |
| to_status | VARCHAR(20) | No | — | New status. |
| changed_by_admin_id | BIGINT UNSIGNED | Yes | NULL | FK → admins.id (NULL = system/user). |
| note | VARCHAR(512) | Yes | NULL | Transition note. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Transition time. |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `withdraw_request_id` → `withdraw_requests.id` `ON DELETE CASCADE`; `changed_by_admin_id` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_wh_request (withdraw_request_id)`; `idx_wh_created (created_at)`.
**6. Unique Constraints** — none.
**7. Relationships** — N—1 `withdraw_requests`; N—1 `admins`.
**8. Validation Rules** — Append-only; one row per status change.
**9. Notes** — Provides the status timeline shown in the app's Withdraw History screen and the admin audit view.

---

# H. Engagement & System

## H.1 `notifications` ⭐

**1. Table Purpose** — Per-user in-app notification inbox (also the persistent record of pushes sent).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| campaign_id | BIGINT UNSIGNED | Yes | NULL | FK → notification_campaigns.id (if broadcast). |
| title | VARCHAR(160) | No | — | Notification title. |
| body | VARCHAR(1000) | No | — | Notification body. |
| type | ENUM('transactional','engagement','promotional','system') | No | 'system' | Category. |
| deep_link | VARCHAR(512) | Yes | NULL | In-app navigation target. |
| image_url | VARCHAR(512) | Yes | NULL | Rich image. |
| data | JSON | Yes | NULL | Structured payload. |
| is_read | TINYINT(1) UNSIGNED | No | 0 | Read state. |
| read_at | DATETIME | Yes | NULL | When read. |
| push_status | ENUM('queued','sent','failed','skipped') | No | 'queued' | FCM delivery state. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE CASCADE`; `campaign_id` → `notification_campaigns.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_notif_user_read_created (user_id, is_read, created_at)` (inbox + badge count — primary read path); `idx_notif_campaign (campaign_id)`; `idx_notif_created (created_at)`.
**6. Unique Constraints** — none.
**7. Relationships** — N—1 `users`; N—1 `notification_campaigns`.
**8. Validation Rules** — Promotional rows honor `user_settings.notif_promotional` and per-device `push_enabled`. Unread-count query served by the composite index.
**9. Notes** — High volume; partition/archival candidate. Push dispatch reads `queued` rows in batches (cron/queue).

---

## H.2 `notification_campaigns`

**1. Table Purpose** — Admin-composed broadcast campaigns (audience, schedule, template, delivery stats).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| title | VARCHAR(160) | No | — | Campaign/notification title. |
| body | VARCHAR(1000) | No | — | Message body. |
| type | ENUM('engagement','promotional','system') | No | 'promotional' | Category. |
| audience | ENUM('all','segment','single') | No | 'all' | Targeting mode. |
| audience_filter | JSON | Yes | NULL | Segment criteria (country, activity, etc.). |
| deep_link | VARCHAR(512) | Yes | NULL | Navigation target. |
| image_url | VARCHAR(512) | Yes | NULL | Rich image. |
| scheduled_at | DATETIME | Yes | NULL | When to send (NULL = immediate). |
| status | ENUM('draft','scheduled','sending','sent','cancelled','failed') | No | 'draft' | Campaign state. |
| total_targeted | INT UNSIGNED | No | 0 | Recipients targeted. |
| total_sent | INT UNSIGNED | No | 0 | Successfully sent. |
| total_failed | INT UNSIGNED | No | 0 | Failures. |
| created_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `created_by` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_nc_status_scheduled (status, scheduled_at)`.
**6. Unique Constraints** — none.
**7. Relationships** — 1—N `notifications`.
**8. Validation Rules** — Sending transitions draft/scheduled → sending → sent; stats updated as batches complete.
**9. Notes** — Large campaigns fan out into `notifications` rows via cron/queue in chunks (shared-hosting friendly).

---

## H.3 `support_tickets`

**1. Table Purpose** — User support requests (subject, status, priority) with threaded messages.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| uuid | CHAR(36) | No | — | Public ticket reference. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| subject | VARCHAR(200) | No | — | Ticket subject. |
| category | VARCHAR(60) | Yes | NULL | Issue category (withdrawal, reward, account). |
| status | ENUM('open','pending','answered','resolved','closed') | No | 'open' | Ticket state. |
| priority | ENUM('low','normal','high','urgent') | No | 'normal' | Priority. |
| assigned_admin_id | BIGINT UNSIGNED | Yes | NULL | FK → admins.id. |
| last_reply_at | DATETIME | Yes | NULL | Timestamp of latest message. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE CASCADE`; `assigned_admin_id` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_st_user (user_id)`; `idx_st_status_priority (status, priority)`; `idx_st_assigned (assigned_admin_id)`.
**6. Unique Constraints** — `uq_st_uuid (uuid)`.
**7. Relationships** — N—1 `users`; 1—N `support_messages`; N—1 `admins`.
**8. Validation Rules** — Status machine; `last_reply_at` maintained on each new message.
**9. Notes** — Powers the app's Support screen and the admin support inbox.

---

## H.4 `support_messages`

**1. Table Purpose** — Threaded messages within a support ticket (from user or admin).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| ticket_id | BIGINT UNSIGNED | No | — | FK → support_tickets.id. |
| sender_type | ENUM('user','admin','system') | No | — | Who authored the message. |
| sender_user_id | BIGINT UNSIGNED | Yes | NULL | FK → users.id (if user). |
| sender_admin_id | BIGINT UNSIGNED | Yes | NULL | FK → admins.id (if admin). |
| message | TEXT | No | — | Message body. |
| attachment_url | VARCHAR(512) | Yes | NULL | Optional attachment. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `ticket_id` → `support_tickets.id` `ON DELETE CASCADE`; `sender_user_id` → `users.id` `ON DELETE SET NULL`; `sender_admin_id` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_sm_ticket_created (ticket_id, created_at)`.
**6. Unique Constraints** — none.
**7. Relationships** — N—1 `support_tickets`; N—1 `users`/`admins`.
**8. Validation Rules** — Exactly one sender id populated per `sender_type`. Append-only.
**9. Notes** — Attachments validated (type/size) and stored outside web root.

---

## H.5 `faqs`

**1. Table Purpose** — Support FAQ content (now optionally grouped under CMS categories).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| category_id | BIGINT UNSIGNED | Yes | NULL | FK → faq_categories.id. |
| question | VARCHAR(255) | No | — | FAQ question. |
| answer | TEXT | No | — | FAQ answer (HTML/markdown). |
| locale | VARCHAR(10) | No | 'en' | Language. |
| sort_order | INT UNSIGNED | No | 0 | Display order. |
| is_published | TINYINT(1) UNSIGNED | No | 1 | Visibility. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `category_id` → `faq_categories.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_faq_category (category_id)`; `idx_faq_published_sort (is_published, sort_order)`.
**6. Unique Constraints** — none.
**7. Relationships** — N—1 `faq_categories`.
**8. Validation Rules** — Answer HTML sanitized on save (XSS). Only published rows served publicly.
**9. Notes** — Managed under CMS Management; multi-locale supported via `locale`.

---

## H.6 `faq_categories`

**1. Table Purpose** — Grouping/ordering for FAQ entries.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| name | VARCHAR(120) | No | — | Category name. |
| slug | VARCHAR(120) | No | — | URL-safe key. |
| sort_order | INT UNSIGNED | No | 0 | Display order. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Visibility. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_fc_active_sort (is_active, sort_order)`.
**6. Unique Constraints** — `uq_fc_slug (slug)`.
**7. Relationships** — 1—N `faqs`.
**8. Validation Rules** — `slug` unique, lowercase.
**9. Notes** — Admin-managed under CMS.

---

## H.7 `app_settings`

**1. Table Purpose** — Global key–value configuration and feature flags not covered by the dedicated config tables (legacy/simple settings). `remote_configs` is the richer, typed, audience-aware superset.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| setting_key | VARCHAR(120) | No | — | Unique key. |
| setting_value | TEXT | Yes | NULL | Value (string-encoded). |
| value_type | ENUM('string','int','bool','json') | No | 'string' | For casting. |
| group_name | VARCHAR(60) | Yes | NULL | Logical grouping. |
| is_public | TINYINT(1) UNSIGNED | No | 0 | Whether exposed to the app. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_as_group (group_name)`; `idx_as_public (is_public)`.
**6. Unique Constraints** — `uq_as_key (setting_key)`.
**7. Relationships** — Standalone.
**8. Validation Rules** — Only `is_public=1` keys returned to clients; secrets never marked public.
**9. Notes** — Frequently read; cache in application memory with invalidation on write.

---

# I. Admin & Governance

## I.1 `admins`

**1. Table Purpose** — Staff/administrator accounts (separate from end users), with role and 2FA.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| role_id | BIGINT UNSIGNED | No | — | FK → admin_roles.id. |
| name | VARCHAR(120) | No | — | Full name. |
| email | VARCHAR(255) | No | — | Login email. |
| password_hash | VARCHAR(255) | No | — | argon2/bcrypt hash. |
| two_fa_secret_enc | VARBINARY(512) | Yes | NULL | Encrypted TOTP secret. |
| two_fa_enabled | TINYINT(1) UNSIGNED | No | 0 | Whether 2FA active. |
| status | ENUM('active','suspended','disabled') | No | 'active' | Account state. |
| last_login_at | DATETIME | Yes | NULL | Last login. |
| last_login_ip | VARCHAR(45) | Yes | NULL | Last login IP. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |
| deleted_at | DATETIME | Yes | NULL | Soft-delete marker. |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `role_id` → `admin_roles.id` `ON DELETE RESTRICT`.
**5. Indexes** — `idx_admins_role (role_id)`; `idx_admins_status (status)`.
**6. Unique Constraints** — `uq_admins_email (email)`.
**7. Relationships** — N—1 `admin_roles`; referenced by audit logs, reviews, campaigns, backups, etc.
**8. Validation Rules** — Strong password policy; 2FA recommended/required for privileged roles; separate credential store from `users`.
**9. Notes** — Never merged with `users`; different auth flow (session + CSRF, not JWT).

---

## I.2 `admin_roles`

**1. Table Purpose** — Roles (super_admin, finance, support, moderator) grouping permissions.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| name | VARCHAR(80) | No | — | Role display name. |
| slug | VARCHAR(60) | No | — | Machine key (super_admin, finance…). |
| description | VARCHAR(255) | Yes | NULL | Role description. |
| is_system | TINYINT(1) UNSIGNED | No | 0 | Protects built-in roles from deletion. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — none beyond unique.
**6. Unique Constraints** — `uq_ar_slug (slug)`.
**7. Relationships** — 1—N `admins`; N—N `admin_permissions` via `admin_role_permissions`.
**8. Validation Rules** — `is_system=1` roles cannot be deleted; `super_admin` always retains all permissions.
**9. Notes** — Realizes the architecture's RBAC roles.

---

## I.3 `admin_permissions`

**1. Table Purpose** — Granular permissions per module/action (e.g., `withdraw.approve`, `user.ban`).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| module | VARCHAR(60) | No | — | Module the permission belongs to. |
| action | VARCHAR(60) | No | — | Action verb (view, create, approve…). |
| slug | VARCHAR(120) | No | — | Composite key `module.action`. |
| description | VARCHAR(255) | Yes | NULL | Human description. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_ap_module (module)`.
**6. Unique Constraints** — `uq_ap_slug (slug)`.
**7. Relationships** — N—N `admin_roles` via `admin_role_permissions`.
**8. Validation Rules** — `slug = module.action`, lowercase. Seeded from a canonical permission list.
**9. Notes** — Checked by RBAC middleware before each admin controller action.

---

## I.4 `admin_role_permissions`

**1. Table Purpose** — Junction table realizing the many-to-many between roles and permissions (documented in the architecture as `admin_roles ↔ admin_permissions`).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| role_id | BIGINT UNSIGNED | No | — | FK → admin_roles.id. |
| permission_id | BIGINT UNSIGNED | No | — | FK → admin_permissions.id. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |

**3. Primary Key** — composite `(role_id, permission_id)`.
**4. Foreign Keys** — `role_id` → `admin_roles.id` `ON DELETE CASCADE`; `permission_id` → `admin_permissions.id` `ON DELETE CASCADE`.
**5. Indexes** — `idx_arp_permission (permission_id)` (reverse lookup; PK covers forward).
**6. Unique Constraints** — the composite PK enforces uniqueness.
**7. Relationships** — Bridges `admin_roles` and `admin_permissions`.
**8. Validation Rules** — No duplicate pairs (PK-enforced).
**9. Notes** — Pure junction; no surrogate id needed.

---

## I.5 `admin_audit_logs`

**1. Table Purpose** — Immutable record of every admin action (actor, action, target, before/after) for governance and forensics.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| admin_id | BIGINT UNSIGNED | Yes | NULL | FK → admins.id (actor). |
| action | VARCHAR(120) | No | — | Action performed (e.g., withdraw.approve). |
| target_type | VARCHAR(80) | Yes | NULL | Affected entity type (table/model). |
| target_id | BIGINT UNSIGNED | Yes | NULL | Affected entity id. |
| before_data | JSON | Yes | NULL | Snapshot before change. |
| after_data | JSON | Yes | NULL | Snapshot after change. |
| ip_address | VARCHAR(45) | Yes | NULL | Actor IP. |
| user_agent | VARCHAR(255) | Yes | NULL | Actor UA. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Action time. |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `admin_id` → `admins.id` `ON DELETE SET NULL` (keep the log even if admin removed).
**5. Indexes** — `idx_aal_admin (admin_id)`; `idx_aal_target (target_type, target_id)`; `idx_aal_action (action)`; `idx_aal_created (created_at)`.
**6. Unique Constraints** — none.
**7. Relationships** — N—1 `admins`.
**8. Validation Rules** — Append-only; never edited/deleted. Sensitive values (secrets) redacted in `before_data`/`after_data`.
**9. Notes** — Written for every mutating admin operation; partition/archival candidate at scale.

---

## I.6 `fraud_flags`

**1. Table Purpose** — Records users/events flagged by the fraud engine with reason and review status; gates withdrawals.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| flag_type | ENUM('multi_account','vpn','emulator','velocity','self_referral','chargeback','manual','other') | No | — | Reason category. |
| severity | ENUM('low','medium','high','critical') | No | 'medium' | Risk level. |
| source_module | VARCHAR(40) | Yes | NULL | Where the flag originated. |
| reference_id | VARCHAR(191) | Yes | NULL | Related event reference. |
| details | JSON | Yes | NULL | Evidence/context. |
| status | ENUM('open','reviewing','confirmed','dismissed') | No | 'open' | Review state. |
| action_taken | ENUM('none','warned','withdrawals_held','suspended','banned') | No | 'none' | Resolution action. |
| reviewed_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id. |
| reviewed_at | DATETIME | Yes | NULL | Review time. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE CASCADE`; `reviewed_by` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_ff_user (user_id)`; `idx_ff_status_severity (status, severity)`; `idx_ff_type (flag_type)`.
**6. Unique Constraints** — `uq_ff_user_type_ref (user_id, flag_type, reference_id)` (avoid duplicate identical flags).
**7. Relationships** — N—1 `users`; N—1 `admins`.
**8. Validation Rules** — Open high/critical flags automatically hold the user's withdrawals until resolved.
**9. Notes** — Feeds the admin fraud review panel; integrates with Withdraw approval.

---

# J. Ads (Multi-Network Management)

## J.1 `ads_placements`

**1. Table Purpose** — Logical ad slots in the app (banner/interstitial/rewarded by screen), resolved to a concrete network unit via the mediation waterfall.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| name | VARCHAR(100) | No | — | Placement name (home_banner, spin_rewarded). |
| code | VARCHAR(60) | No | — | Machine code used by the app. |
| ad_format | ENUM('banner','interstitial','rewarded','native','app_open') | No | — | Ad format. |
| reward_coins | BIGINT UNSIGNED | No | 0 | Coins for rewarded placements. |
| daily_cap_per_user | INT UNSIGNED | Yes | NULL | Max rewarded views/user/day. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Whether the slot is live. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_apl_active (is_active)`; `idx_apl_format (ad_format)`.
**6. Unique Constraints** — `uq_apl_code (code)`.
**7. Relationships** — 1—N `ad_units`; 1—N `ad_network_events`.
**8. Validation Rules** — Rewarded placements must have `reward_coins > 0`; daily cap enforced server-side before crediting.
**9. Notes** — App references placements by `code`; the active network/unit is resolved server-side.

---

## J.2 `ad_networks`

**1. Table Purpose** — Registered ad networks (**AdMob, AppLovin MAX, Unity Ads**) with credentials and an enable/disable flag controlled from Admin.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| network | ENUM('admob','applovin_max','unity_ads') | No | — | Network identifier. |
| display_name | VARCHAR(80) | No | — | Human name. |
| app_id | VARCHAR(191) | Yes | NULL | Network app id. |
| api_key_enc | VARBINARY(512) | Yes | NULL | Encrypted API/SDK key. |
| config | JSON | Yes | NULL | Extra network config. |
| is_enabled | TINYINT(1) UNSIGNED | No | 1 | **Admin enable/disable toggle.** |
| priority | INT UNSIGNED | No | 100 | Global mediation priority (lower = higher). |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_an_enabled_priority (is_enabled, priority)`.
**6. Unique Constraints** — `uq_an_network (network)`.
**7. Relationships** — 1—N `ad_units`; 1—N `ad_network_events`.
**8. Validation Rules** — Secrets **encrypted**, write-only in UI. Disabling a network removes its units from the waterfall instantly (no app release).
**9. Notes** — Directly satisfies the architecture's "enable/disable from Admin" requirement.

---

## J.3 `ad_units`

**1. Table Purpose** — Concrete per-network ad unit IDs mapped to placements, with per-country priority for the mediation waterfall.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| network_id | BIGINT UNSIGNED | No | — | FK → ad_networks.id. |
| placement_id | BIGINT UNSIGNED | No | — | FK → ads_placements.id. |
| ad_unit_id | VARCHAR(191) | No | — | Network's ad unit identifier. |
| ad_format | ENUM('banner','interstitial','rewarded','native','app_open') | No | — | Format (must match placement). |
| country_code | CHAR(2) | Yes | NULL | Optional geo-targeting (NULL = all). |
| priority | INT UNSIGNED | No | 100 | Waterfall order within the placement. |
| ecpm_floor | DECIMAL(10,4) | Yes | NULL | Optional eCPM floor. |
| is_enabled | TINYINT(1) UNSIGNED | No | 1 | Unit-level toggle. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `network_id` → `ad_networks.id` `ON DELETE CASCADE`; `placement_id` → `ads_placements.id` `ON DELETE CASCADE`.
**5. Indexes** — `idx_au_placement_priority (placement_id, is_enabled, priority)` (waterfall resolution); `idx_au_network (network_id)`; `idx_au_country (country_code)`.
**6. Unique Constraints** — `uq_au_network_unit (network_id, ad_unit_id)`.
**7. Relationships** — N—1 `ad_networks`; N—1 `ads_placements`.
**8. Validation Rules** — `ad_format` must equal the parent placement's format. Only units whose network `is_enabled=1` participate.
**9. Notes** — The `/ads/config` endpoint returns the ordered, enabled units per placement (public unit IDs only; no secrets).

---

## J.4 `ad_network_events`

**1. Table Purpose** — Impression/completion logs for rewarded ads — feeds fraud checks, reward crediting audit, and reporting.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| network_id | BIGINT UNSIGNED | Yes | NULL | FK → ad_networks.id. |
| placement_id | BIGINT UNSIGNED | Yes | NULL | FK → ads_placements.id. |
| unit_id | BIGINT UNSIGNED | Yes | NULL | FK → ad_units.id. |
| event_type | ENUM('impression','click','rewarded_start','rewarded_complete','failed') | No | — | Event kind. |
| ssv_token | VARCHAR(255) | Yes | NULL | Server-side-verification token/nonce (rewarded). |
| ssv_verified | TINYINT(1) UNSIGNED | No | 0 | Whether SSV/callback validated. |
| reward_coins | BIGINT UNSIGNED | No | 0 | Coins credited (if any). |
| transaction_id | BIGINT UNSIGNED | Yes | NULL | FK → wallet_transactions.id. |
| ip_address | VARCHAR(45) | Yes | NULL | Event IP. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | Event time. |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `user_id` → `users.id` `ON DELETE CASCADE`; `network_id` → `ad_networks.id` `ON DELETE SET NULL`; `placement_id` → `ads_placements.id` `ON DELETE SET NULL`; `unit_id` → `ad_units.id` `ON DELETE SET NULL`; `transaction_id` → `wallet_transactions.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_ane_user_created (user_id, created_at)`; `idx_ane_type (event_type)`; `idx_ane_placement (placement_id)`.
**6. Unique Constraints** — `uq_ane_ssv_token (ssv_token)` — prevents replaying a rewarded completion.
**7. Relationships** — N—1 `users`, `ad_networks`, `ads_placements`, `ad_units`; 1—1 ledger row on credit.
**8. Validation Rules** — Coins credited **only** when `ssv_verified=1` and within `daily_cap_per_user`; idempotent via `ssv_token` + ledger `reference_id`.
**9. Notes** — High volume; partition/archival candidate. Closes the fake-rewarded-ad exploit (server verification).

---

# K. Payments

## K.1 `payment_gateways`

**1. Table Purpose** — Configured payout/collection gateways (UPI/Razorpay, PayPal, Paytm, gift-card, bank) with credentials, fees, limits, and enable/disable.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| name | VARCHAR(80) | No | — | Gateway display name. |
| code | VARCHAR(40) | No | — | Machine code (razorpay, paypal…). |
| type | ENUM('payout','collection','both') | No | 'payout' | Direction supported. |
| credentials_enc | VARBINARY(1024) | Yes | NULL | Encrypted API credentials. |
| config | JSON | Yes | NULL | Non-secret config (endpoints, mode). |
| mode | ENUM('sandbox','live') | No | 'sandbox' | Environment. |
| fee_percent | DECIMAL(6,4) | No | 0.0000 | Gateway fee fraction. |
| fee_flat | DECIMAL(18,4) | No | 0.0000 | Flat fee in cash. |
| min_amount | DECIMAL(18,4) | Yes | NULL | Min payout amount. |
| max_amount | DECIMAL(18,4) | Yes | NULL | Max payout amount. |
| supported_currencies | JSON | Yes | NULL | Allowed currency codes. |
| is_enabled | TINYINT(1) UNSIGNED | No | 1 | **Admin enable/disable.** |
| priority | INT UNSIGNED | No | 100 | Selection priority. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_pg_enabled_priority (is_enabled, priority)`.
**6. Unique Constraints** — `uq_pg_code (code)`.
**7. Relationships** — 1—N `withdraw_methods`; 1—N `gateway_transactions`.
**8. Validation Rules** — Credentials **encrypted at rest**, write-only in UI, never returned to clients. `mode='live'` requires complete config. Disabling hides mapped methods.
**9. Notes** — Abstracts execution from the Withdraw request lifecycle; adding a provider is config, not code.

---

## K.2 `gateway_transactions`

**1. Table Purpose** — Execution records for payouts routed through a gateway (status, external reference, idempotency).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| gateway_id | BIGINT UNSIGNED | No | — | FK → payment_gateways.id. |
| withdraw_request_id | BIGINT UNSIGNED | No | — | FK → withdraw_requests.id. |
| idempotency_key | VARCHAR(191) | No | — | Client/server idempotency key for the gateway call. |
| direction | ENUM('payout','collection') | No | 'payout' | Flow direction. |
| amount | DECIMAL(18,4) | No | — | Amount sent. |
| currency_code | CHAR(3) | No | 'INR' | Currency. |
| status | ENUM('initiated','processing','success','failed','refunded') | No | 'initiated' | Gateway result. |
| external_reference | VARCHAR(191) | Yes | NULL | Provider transaction id. |
| request_payload | JSON | Yes | NULL | Outbound payload (redacted). |
| response_payload | JSON | Yes | NULL | Gateway response (redacted). |
| error_code | VARCHAR(80) | Yes | NULL | Failure code. |
| error_message | VARCHAR(512) | Yes | NULL | Failure detail. |
| processed_at | DATETIME | Yes | NULL | Completion time. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `gateway_id` → `payment_gateways.id` `ON DELETE RESTRICT`; `withdraw_request_id` → `withdraw_requests.id` `ON DELETE RESTRICT`.
**5. Indexes** — `idx_gt_request (withdraw_request_id)`; `idx_gt_gateway_status (gateway_id, status)`; `idx_gt_created (created_at)`.
**6. Unique Constraints** — `uq_gt_idempotency (gateway_id, idempotency_key)`; `uq_gt_external (gateway_id, external_reference)`.
**7. Relationships** — N—1 `payment_gateways`; N—1 `withdraw_requests`.
**8. Validation Rules** — Idempotency prevents duplicate payouts on retry. Webhook/callback signatures verified before status transition to `success`. Sensitive fields redacted in stored payloads.
**9. Notes** — Complements `withdraw_requests` (authorization) with execution detail; partition/archival candidate at scale.

---

# L. Content & Config (Server-Driven)

## L.1 `banners`

**1. Table Purpose** — Promotional banners shown on home/promo surfaces, fully admin-controlled without app releases.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| title | VARCHAR(160) | Yes | NULL | Banner title/label. |
| image_url | VARCHAR(512) | No | — | Banner image. |
| placement | VARCHAR(60) | No | 'home_top' | Where it appears. |
| action_type | ENUM('none','deep_link','url','offer','task') | No | 'none' | Tap action type. |
| action_value | VARCHAR(512) | Yes | NULL | Target (deep link/url/id). |
| target_audience | JSON | Yes | NULL | Segment filter. |
| sort_order | INT UNSIGNED | No | 0 | Display order. |
| starts_at | DATETIME | Yes | NULL | Active window start. |
| ends_at | DATETIME | Yes | NULL | Active window end. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Visibility. |
| created_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `created_by` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_ban_active_window (is_active, starts_at, ends_at)`; `idx_ban_placement_sort (placement, sort_order)`.
**6. Unique Constraints** — none.
**7. Relationships** — Standalone (referenced by app home).
**8. Validation Rules** — `action_value` validated against an **allowlisted** scheme (no open redirects). Window valid.
**9. Notes** — Read-heavy, cache-friendly; served by `/banners`.

---

## L.2 `announcements`

**1. Table Purpose** — In-app announcements (bar/popup) with audience, priority, and scheduling.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| title | VARCHAR(160) | No | — | Announcement title. |
| body | TEXT | No | — | Content. |
| display_type | ENUM('bar','popup','card') | No | 'bar' | Presentation style. |
| priority | INT UNSIGNED | No | 100 | Ordering/precedence. |
| action_type | ENUM('none','deep_link','url') | No | 'none' | Optional CTA. |
| action_value | VARCHAR(512) | Yes | NULL | CTA target. |
| target_audience | JSON | Yes | NULL | Segment filter. |
| is_dismissible | TINYINT(1) UNSIGNED | No | 1 | Whether user can dismiss. |
| starts_at | DATETIME | Yes | NULL | Active window start. |
| ends_at | DATETIME | Yes | NULL | Active window end. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Visibility. |
| created_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `created_by` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_ann_active_window (is_active, starts_at, ends_at)`; `idx_ann_priority (priority)`.
**6. Unique Constraints** — none.
**7. Relationships** — 1—N `announcement_reads`.
**8. Validation Rules** — `action_value` allowlisted; body sanitized. Window valid.
**9. Notes** — Served by `/announcements`; per-user dismissal tracked separately.

---

## L.3 `announcement_reads`

**1. Table Purpose** — Per-user seen/dismissed state for announcements (avoids re-showing).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| announcement_id | BIGINT UNSIGNED | No | — | FK → announcements.id. |
| user_id | BIGINT UNSIGNED | No | — | FK → users.id. |
| seen_at | DATETIME | No | CURRENT_TIMESTAMP | When seen/dismissed. |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `announcement_id` → `announcements.id` `ON DELETE CASCADE`; `user_id` → `users.id` `ON DELETE CASCADE`.
**5. Indexes** — `idx_anr_user (user_id)`.
**6. Unique Constraints** — `uq_anr_ann_user (announcement_id, user_id)` — one read record per user per announcement.
**7. Relationships** — N—1 `announcements`; N—1 `users`.
**8. Validation Rules** — Upsert on dismissal.
**9. Notes** — Small, high-churn; can be pruned when an announcement expires.

---

## L.4 `app_versions`

**1. Table Purpose** — Per-platform version control driving **Force Update** decisions at launch.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| platform | ENUM('android','ios') | No | 'android' | Target platform. |
| latest_version | VARCHAR(20) | No | — | Latest available version name. |
| latest_version_code | INT UNSIGNED | No | — | Latest numeric build code. |
| min_supported_code | INT UNSIGNED | No | — | Minimum allowed build code. |
| force_update | TINYINT(1) UNSIGNED | No | 0 | Whether below-min is hard-blocked. |
| changelog | TEXT | Yes | NULL | Release notes. |
| store_url | VARCHAR(512) | Yes | NULL | Store listing URL. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Active record for the platform. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — none.
**5. Indexes** — `idx_av_platform_active (platform, is_active)`.
**6. Unique Constraints** — one active row per platform (app-enforced).
**7. Relationships** — Standalone; queried by `/app/version`.
**8. Validation Rules** — `min_supported_code <= latest_version_code`. If `force_update=1` and client code `< min_supported_code`, app shows a hard update gate.
**9. Notes** — Version history retained via inactive rows.

---

## L.5 `maintenance_windows`

**1. Table Purpose** — Controls **Maintenance Mode** (global app gate) with message and optional schedule.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| is_enabled | TINYINT(1) UNSIGNED | No | 0 | Whether maintenance is active. |
| title | VARCHAR(160) | Yes | NULL | Gate title. |
| message | TEXT | Yes | NULL | User-facing message. |
| scheduled_start | DATETIME | Yes | NULL | Optional start. |
| scheduled_end | DATETIME | Yes | NULL | Optional end. |
| allow_admin_bypass | TINYINT(1) UNSIGNED | No | 1 | Admin/testers can bypass. |
| created_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `created_by` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_mw_enabled (is_enabled)`.
**6. Unique Constraints** — one enabled row at a time (app-enforced).
**7. Relationships** — Standalone; queried by `/app/maintenance`.
**8. Validation Rules** — When enabled (or within schedule), earn/withdraw endpoints return a hard maintenance gate server-side. `scheduled_end > scheduled_start`.
**9. Notes** — Super-Admin-only toggle (§6.3); evaluated server-side so it cannot be bypassed by clients.

---

## L.6 `remote_configs`

**1. Table Purpose** — Typed, audience-aware feature flags and config values delivered to clients live — the authoritative Remote Configuration store (superset of `app_settings`).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| config_key | VARCHAR(120) | No | — | Config key. |
| value_type | ENUM('bool','int','float','string','json') | No | 'string' | Value type for casting. |
| value | TEXT | Yes | NULL | Serialized value. |
| environment | ENUM('production','staging','all') | No | 'all' | Scope. |
| audience_segment | JSON | Yes | NULL | Optional targeting (country, version, %rollout). |
| description | VARCHAR(255) | Yes | NULL | Purpose note. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Whether served. |
| updated_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `updated_by` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_rc_active (is_active)`; `idx_rc_env (environment)`.
**6. Unique Constraints** — `uq_rc_key_env (config_key, environment)`.
**7. Relationships** — Standalone; served by `/config`.
**8. Validation Rules** — `value` must parse per `value_type`. Served **read-only** to clients; flags cannot override server-side money math. All changes audited.
**9. Notes** — Enables gradual rollout, kill-switches, and A/B tests without redeploys (§12). Cache with short TTL + invalidation on write.

---

## L.7 `cms_pages`

**1. Table Purpose** — Editable legal/content pages (Privacy Policy, Terms, About, and other slugs), versioned and locale-aware.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| slug | VARCHAR(80) | No | — | Page key (privacy-policy, terms, about). |
| title | VARCHAR(200) | No | — | Page title. |
| body | MEDIUMTEXT | No | — | Content (sanitized HTML/markdown). |
| locale | VARCHAR(10) | No | 'en' | Language. |
| version | INT UNSIGNED | No | 1 | Content version. |
| is_published | TINYINT(1) UNSIGNED | No | 1 | Publish state. |
| effective_at | DATETIME | Yes | NULL | When the version takes effect. |
| updated_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `updated_by` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_cms_published (is_published)`.
**6. Unique Constraints** — `uq_cms_slug_locale_version (slug, locale, version)`.
**7. Relationships** — Standalone; served by `/cms/{slug}`.
**8. Validation Rules** — Body **HTML-sanitized** on save (stored-XSS defense). Only the latest published version per slug/locale is served; older versions retained for legal history.
**9. Notes** — Legal-content versioning supports "Terms updated on <date>" flows and safe rollback.

---

## L.8 `home_sections`

**1. Table Purpose** — Server-driven, ordered home-screen blocks enabling a dynamic home layout without app releases.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| section_type | ENUM('banner_carousel','quick_actions','offers','tasks','leaderboard','scratch','spin','custom') | No | — | Block type. |
| title | VARCHAR(160) | Yes | NULL | Section heading. |
| config | JSON | Yes | NULL | Block-specific configuration. |
| sort_order | INT UNSIGNED | No | 0 | Vertical order. |
| target_audience | JSON | Yes | NULL | Segment filter. |
| is_active | TINYINT(1) UNSIGNED | No | 1 | Visibility. |
| starts_at | DATETIME | Yes | NULL | Active window start. |
| ends_at | DATETIME | Yes | NULL | Active window end. |
| created_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `created_by` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_hs_active_sort (is_active, sort_order)`.
**6. Unique Constraints** — none.
**7. Relationships** — Standalone; served by `/home/layout`.
**8. Validation Rules** — `config` schema validated per `section_type`. Unknown types are ignored gracefully by the client (forward-compatible).
**9. Notes** — Read-heavy, cache-friendly; the app renders via a section/widget registry.

---

## L.9 `themes`

**1. Table Purpose** — Runtime theme definitions (colors, logo, font, light/dark defaults) applied app-wide.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| name | VARCHAR(80) | No | — | Theme name. |
| primary_color | CHAR(7) | No | — | #RRGGBB primary. |
| secondary_color | CHAR(7) | Yes | NULL | #RRGGBB secondary. |
| accent_color | CHAR(7) | Yes | NULL | #RRGGBB accent. |
| background_color | CHAR(7) | Yes | NULL | #RRGGBB background. |
| logo_url | VARCHAR(512) | Yes | NULL | Logo asset. |
| font_family | VARCHAR(80) | Yes | NULL | Font. |
| default_mode | ENUM('system','light','dark') | No | 'system' | Default theme mode. |
| extra | JSON | Yes | NULL | Additional tokens. |
| is_active | TINYINT(1) UNSIGNED | No | 0 | Whether this is the live theme. |
| created_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `created_by` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_themes_active (is_active)`.
**6. Unique Constraints** — only one `is_active=1` (app-enforced); `uq_themes_name (name)`.
**7. Relationships** — Standalone; served by `/theme`.
**8. Validation Rules** — Color columns validated as `#RRGGBB`. Logo upload validated (type/size). Activating a theme deactivates others atomically.
**9. Notes** — App fetches active theme at launch (with a safe local fallback); changes apply on next launch/refresh.

---

# M. Operations

## M.1 `backup_jobs`

**1. Table Purpose** — Records database backup operations (manual/scheduled), their location, and integrity metadata.

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| type | ENUM('manual','scheduled') | No | 'manual' | Trigger source. |
| status | ENUM('queued','running','success','failed') | No | 'queued' | Job state. |
| storage_location | ENUM('local','s3','gcs','ftp','other') | No | 'local' | Where the backup lives. |
| file_path | VARCHAR(512) | Yes | NULL | Path/key (outside web root). |
| file_size_bytes | BIGINT UNSIGNED | Yes | NULL | Backup size. |
| checksum | CHAR(64) | Yes | NULL | SHA-256 integrity checksum. |
| is_encrypted | TINYINT(1) UNSIGNED | No | 1 | Whether the file is encrypted. |
| started_at | DATETIME | Yes | NULL | Start time. |
| completed_at | DATETIME | Yes | NULL | Completion time. |
| error_message | VARCHAR(512) | Yes | NULL | Failure detail. |
| created_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id (NULL = system/cron). |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |
| updated_at | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `created_by` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_bj_status (status)`; `idx_bj_created (created_at)`.
**6. Unique Constraints** — none.
**7. Relationships** — 1—N `restore_logs`.
**8. Validation Rules** — Backup files stored **off the public web root** and encrypted at rest; checksum verified before any restore.
**9. Notes** — On shared hosting, scheduled via cron export to off-server storage (§12); managed under Backup & Restore (Super-Admin only).

---

## M.2 `restore_logs`

**1. Table Purpose** — Audit of database restore operations (source backup, status, actor).

**2. Columns**

| Name | Data Type | Nullable | Default | Description |
|------|-----------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK. |
| backup_job_id | BIGINT UNSIGNED | No | — | FK → backup_jobs.id (source). |
| status | ENUM('initiated','running','success','failed') | No | 'initiated' | Restore state. |
| checksum_verified | TINYINT(1) UNSIGNED | No | 0 | Whether integrity check passed. |
| performed_by | BIGINT UNSIGNED | Yes | NULL | FK → admins.id. |
| notes | VARCHAR(512) | Yes | NULL | Context/reason. |
| error_message | VARCHAR(512) | Yes | NULL | Failure detail. |
| started_at | DATETIME | Yes | NULL | Start time. |
| completed_at | DATETIME | Yes | NULL | Completion time. |
| created_at | DATETIME | No | CURRENT_TIMESTAMP | — |

**3. Primary Key** — `id`.
**4. Foreign Keys** — `backup_job_id` → `backup_jobs.id` `ON DELETE RESTRICT`; `performed_by` → `admins.id` `ON DELETE SET NULL`.
**5. Indexes** — `idx_rl_backup (backup_job_id)`; `idx_rl_status (status)`.
**6. Unique Constraints** — none.
**7. Relationships** — N—1 `backup_jobs`; N—1 `admins`.
**8. Validation Rules** — Restore requires `checksum_verified=1`, Super-Admin auth, and dual-confirmation (§6.3). Append-only.
**9. Notes** — Every restore is a high-risk, fully audited event.

---

# N. Cross-Cutting Data Architecture Notes

### N.1 Idempotency Map (where double-processing is prevented)

| Concern | Guarding Unique Key |
|---------|---------------------|
| Any coin credit/debit | `wallet_transactions.uq_wt_reference (reference_id)` |
| Offerwall/CPA conversions | `offer_conversions.uq_ocv_provider_txn (provider_id, transaction_id_ext)` |
| Rewarded ads | `ad_network_events.uq_ane_ssv_token` |
| Daily check-in | `daily_checkins.uq_dc_user_date` |
| Referral (one referrer per user) | `referrals.uq_ref_referee` |
| Referral commission | `referral_earnings.uq_re_source_txn` |
| Callback tasks | `task_completions.uq_tc_user_task_ref` |
| Gateway payouts | `gateway_transactions.uq_gt_idempotency` |

### N.2 Financial Integrity Invariants

1. Balances (`wallets`) mutate **only** alongside a `wallet_transactions` insert, inside one row-locked DB transaction.
2. `wallet_transactions` is **append-only**; corrections use `reversal` rows, never edits/deletes.
3. Withdrawals use **hold → settle/release** so reserved coins cannot be double-spent.
4. A nightly reconciliation asserts `wallet.coin_balance == SUM(ledger deltas)`; drift raises an ops alert / `fraud_flags`.

### N.3 Referential Delete Strategy

- Users with financial history are **never hard-deleted** — they are anonymized (PII nulled, `status='deleted'`, `deleted_at` set), preserving ledger and audit integrity (FKs use `RESTRICT` toward financial parents).
- Owned, value-less child rows cascade; optional references use `SET NULL`.

### N.4 High-Volume / Partition & Archival Candidates

`wallet_transactions`, `postback_logs`, `ad_network_events`, `offer_clicks`, `notifications`, `gateway_transactions`, `admin_audit_logs` — all append-only and time-ordered. Strategy: monthly `RANGE` partition on `created_at`; archive/purge cold partitions per retention policy after cloud migration (§12).

### N.5 Encryption at Rest (application-layer)

Encrypted columns (`*_enc`): KYC document numbers, offerwall/provider secrets, ad-network keys, payment-gateway credentials, admin 2FA secrets. Keys managed outside the DB (env/KMS); these values are never returned to clients and are masked/write-only in the admin UI.

### N.6 Caching Strategy for Server-Driven Config

`banners`, `announcements`, `home_sections`, `themes`, `remote_configs`, `app_versions`, `maintenance_windows`, `cms_pages`, `app_settings` are read-heavy and change rarely — cache in application/CDN with short TTL and explicit invalidation on admin publish.

---

*End of Database Design — CashNest (companion to SAD v1.1). No SQL generated; this is a design specification.*
