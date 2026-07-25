# CashNest — Software Architecture Document (SAD)

> **Project:** CashNest
> **Type:** Rewards & Earning Platform
> **Author:** Software Architecture Team
> **Status:** Blueprint — v1.1
> **Scope:** End-to-end architecture for Flutter Android app, PHP MVC backend, PHP admin panel, MySQL database, Firebase Cloud Messaging, shared hosting, GitHub repository, and GitHub Actions APK builds.
>
> **Changelog:**
> - **v1.1** — Added 10 production-ready modules: Multi Ad Network Management, Payment Gateway Management, Banner Management, Announcement System, App Version Management, Remote Configuration System, CMS Module, Database Backup & Restore, Dynamic Home Screen Management, Theme Management. Updated Folder Structure, Database, API, Admin Panel, Flutter, Security, and Scalability sections accordingly. All v1.0 content retained.
> - **v1.0** — Initial complete architecture blueprint.

---

## Table of Contents

1. [Overall System Architecture](#1-overall-system-architecture)
2. [Folder Structure](#2-folder-structure)
3. [Module Structure](#3-module-structure)
4. [Database Planning](#4-database-planning)
5. [API Planning](#5-api-planning)
6. [Admin Panel Planning](#6-admin-panel-planning)
7. [Flutter App Planning](#7-flutter-app-planning)
8. [Security Architecture](#8-security-architecture)
9. [Firebase Integration Plan](#9-firebase-integration-plan)
10. [GitHub Repository Structure](#10-github-repository-structure)
11. [GitHub Actions Strategy](#11-github-actions-strategy)
12. [Future Scalability Plan](#12-future-scalability-plan)

---

## 1. Overall System Architecture

### 1.1 Architectural Style

CashNest follows a **client–server, layered, service-oriented architecture** with a clear separation between:

- **Presentation Tier** — Flutter Android app (end users) and PHP server-rendered Admin Panel (operators).
- **Application Tier** — PHP MVC REST API backend implementing all business logic, exposed over HTTPS.
- **Data Tier** — MySQL relational database as the single source of truth.
- **External Services Tier** — Firebase Cloud Messaging (push), Google OAuth (identity), and third-party Offerwall / CPA networks (revenue).

The backend is the **authoritative core**: the app and the admin panel are both thin clients that never touch the database directly. All reward math, balance mutations, fraud checks, and withdrawals are enforced server-side.

### 1.2 High-Level Diagram

```
                         ┌──────────────────────────────┐
                         │        END USERS (Android)     │
                         │      Flutter Android App        │
                         └───────────────┬────────────────┘
                                         │ HTTPS / JSON (JWT)
                                         │
        Google OAuth ───────────────┐    │    ┌──────────── Firebase Cloud Messaging
        (Sign-In)                   │    │    │             (Push Notifications)
                                    ▼    ▼    ▲
                         ┌──────────────────────────────┐
                         │      PHP MVC REST BACKEND      │
                         │  (Controllers → Services →     │
                         │   Repositories → Models)       │
                         │  Auth | Wallet | Rewards |     │
                         │  Offerwall | Referral | Admin  │
                         └───────┬──────────────┬─────────┘
                                 │              │
                Offerwall / CPA  │              │  MySQL (PDO)
                Postback Callbacks│             ▼
                (server-to-server)│      ┌───────────────┐
                                 ▼       │  MySQL DB      │
                         ┌───────────────┤  (InnoDB)      │
                         │  PHP ADMIN     └───────────────┘
                         │  PANEL (MVC,   │
                         │  server-side   │
                         │  rendered)     │
                         └────────────────┘
                            OPERATORS / STAFF
```

### 1.3 Communication Patterns

| From | To | Protocol | Auth |
|------|----|----------|------|
| Flutter App | Backend REST API | HTTPS / JSON | JWT (Bearer) |
| Admin Panel | Backend Services / DB | Internal (same host) | Session + CSRF |
| Backend | Firebase FCM | HTTPS (FCM HTTP v1) | Service Account (OAuth2) |
| Google | App / Backend | OAuth 2.0 / OIDC | ID Token verification |
| Offerwall/CPA Networks | Backend | HTTPS Postback (server-to-server) | Signed hash / IP allowlist |
| Backend | MySQL | TCP (PDO) | DB credentials (env) |

### 1.4 Deployment Topology (Shared Hosting)

Because the platform targets **shared hosting**, the architecture is intentionally deployable without root access, containers, or long-running daemons:

- **Single PHP application** on the shared host serving two entry points under one domain:
  - `api.cashnest.app` (or `/api`) → REST API for the app.
  - `admin.cashnest.app` (or `/admin`) → Admin panel.
- **MySQL** provided by the host (single instance, InnoDB engine).
- **Cron jobs** (via cPanel/hosting cron) for scheduled work: leaderboard recomputation, expiring scratch cards, referral maturation, notification dispatch, and stale-request cleanup — since shared hosting has no persistent worker process.
- **Public web root** exposes only the `public/` directory; all framework, config, and vendor code sit **above** the web root or are protected via `.htaccess` deny rules.
- **Firebase Admin SDK / HTTP v1** invoked directly from PHP for push (no separate Node service needed).

### 1.5 Key Architectural Principles

1. **Server is the source of truth** — the client never computes balances or reward eligibility.
2. **Idempotency everywhere money moves** — every credit/debit carries a unique reference to prevent double-crediting from retries or duplicate postbacks.
3. **Ledger-based wallet** — balances are derived/validated against an immutable transaction ledger, never edited in place blindly.
4. **Stateless API** — JWT-authenticated, horizontally portable, no server session for the app.
5. **Defense in depth** — validation, authz, rate limiting, and fraud checks at multiple layers.
6. **Config over code** — reward values, offerwall keys, and feature flags live in DB/config, editable from admin without redeploy.

---

## 2. Folder Structure

### 2.1 Monorepo Top-Level Layout

```
cashnest/
├── backend/                  # PHP MVC REST API
├── admin/                    # PHP MVC Admin Panel
├── mobile/                   # Flutter Android app
├── database/                 # Migrations & seed definitions (schema-as-docs)
├── docs/                     # Architecture, API contracts, runbooks
├── .github/                  # GitHub Actions workflows & templates
├── scripts/                  # Deployment / cron / utility scripts
└── README.md
```

### 2.2 Backend (PHP MVC REST API)

```
backend/
├── public/                       # WEB ROOT (only folder exposed publicly)
│   ├── index.php                 # Front controller / bootstrap
│   └── .htaccess                 # Rewrite + security headers
├── app/
│   ├── Controllers/              # HTTP layer — request/response only
│   │   ├── Auth/
│   │   ├── Wallet/
│   │   ├── Rewards/
│   │   ├── Offerwall/
│   │   ├── Referral/
│   │   ├── Tasks/
│   │   ├── Leaderboard/
│   │   ├── Withdraw/
│   │   ├── Profile/
│   │   ├── Notification/
│   │   ├── Support/
│   │   ├── Ads/                  # Multi ad-network config (AdMob/MAX/Unity)
│   │   ├── Payment/              # Payment gateway management
│   │   ├── Banner/              # Banner management
│   │   ├── Announcement/        # Announcement system
│   │   ├── AppVersion/          # Force update / maintenance mode
│   │   ├── RemoteConfig/        # Remote configuration
│   │   ├── Cms/                 # Privacy / Terms / About / FAQ
│   │   ├── Backup/             # DB backup & restore (admin-only)
│   │   ├── HomeLayout/         # Dynamic home screen
│   │   └── Theme/              # Theme management
│   ├── Services/                 # Business logic (reward math, fraud, ledger)
│   │   # + AdNetworkService, PaymentGatewayService, BannerService,
│   │   #   AnnouncementService, AppVersionService, RemoteConfigService,
│   │   #   CmsService, BackupService, HomeLayoutService, ThemeService
│   ├── Repositories/             # Data access (PDO queries per aggregate)
│   ├── Models/                   # Domain entities / DTOs
│   ├── Middleware/               # Auth (JWT), RateLimit, CORS, Validation
│   ├── Requests/                 # Input validation rules per endpoint
│   ├── Resources/                # API response transformers (JSON shaping)
│   ├── Events/                   # Domain events (e.g., WalletCredited)
│   ├── Jobs/                     # Cron-invoked tasks
│   ├── Helpers/                  # Utilities (hashing, signing, formatting)
│   └── Exceptions/               # Custom exceptions + handler
├── config/                       # app, database, jwt, firebase, offerwall configs
├── routes/
│   ├── api.php                   # Route → controller mapping
│   └── postback.php              # Offerwall/CPA callback routes
├── core/                         # Micro-framework kernel (Router, Container, Request, Response)
├── storage/                      # logs/, cache/, uploads/ (writable, non-public)
├── database/                     # Migration & seeder scripts (PHP-defined)
├── tests/                        # Unit + feature tests
├── vendor/                       # Composer deps (not committed)
├── .env.example
├── composer.json
└── phpunit.xml
```

### 2.3 Admin Panel (PHP MVC, server-rendered)

```
admin/
├── public/
│   ├── index.php
│   ├── assets/                   # CSS, JS, images (bundled)
│   └── .htaccess
├── app/
│   ├── Controllers/             # Dashboard, Users, Wallet, Rewards, Offerwall,
│   │                            #   Ads/Networks, Payment, Banner, Announcement,
│   │                            #   AppVersion, RemoteConfig, Cms, Backup,
│   │                            #   HomeLayout, Theme, Withdraw, Referral, ...
│   ├── Services/                # Reuses/wraps backend service layer where shared
│   ├── Repositories/
│   ├── Views/                   # Server-rendered templates (layout, partials, pages)
│   ├── Middleware/              # AdminAuth, RBAC, CSRF, AuditLog
│   └── Requests/
├── config/
├── routes/
│   └── web.php
├── storage/                     # logs/, exports/ (report CSVs)
└── composer.json
```

> **Shared core note:** `backend/core` and the service layer are packaged as an internal Composer package (`cashnest/core`) so the admin panel reuses the same domain/business logic instead of duplicating reward and wallet rules.

### 2.4 Flutter Android App

```
mobile/
├── android/                     # Native Android config, signing, google-services.json
├── lib/
│   ├── main.dart
│   ├── app/                     # App root, theme, routing
│   ├── core/
│   │   ├── network/             # API client, interceptors, error mapping
│   │   ├── storage/             # Secure storage, cache
│   │   ├── config/              # Env, constants, endpoints
│   │   ├── di/                  # Dependency injection setup
│   │   └── utils/
│   ├── data/
│   │   ├── models/              # DTOs / JSON models
│   │   ├── datasources/         # Remote (API) + local sources
│   │   └── repositories/        # Repository implementations
│   ├── domain/
│   │   ├── entities/
│   │   ├── repositories/        # Abstract contracts
│   │   └── usecases/
│   ├── presentation/
│   │   ├── features/            # One folder per feature (see Module Structure)
│   │   │   ├── auth/
│   │   │   ├── home/
│   │   │   ├── wallet/
│   │   │   ├── rewards/
│   │   │   ├── offerwall/
│   │   │   ├── referral/
│   │   │   ├── tasks/
│   │   │   ├── leaderboard/
│   │   │   ├── withdraw/
│   │   │   ├── profile/
│   │   │   ├── notifications/
│   │   │   ├── settings/
│   │   │   ├── support/
│   │   │   ├── announcements/   # In-app announcement bar/popup
│   │   │   └── legal/           # CMS pages: Privacy, Terms, About, FAQ
│   │   ├── widgets/             # Shared reusable widgets (+ dynamic banner, home blocks)
│   │   └── state/              # State management (Bloc/Riverpod/Provider)
│   └── services/               # FCM, analytics, deep links,
│                               #   ad_networks (AdMob/MAX/Unity mediation),
│                               #   remote_config, theme_service, version_gate
├── assets/                      # Images, fonts, lottie, translations
├── test/
└── pubspec.yaml
```

### 2.5 Supporting Folders

```
database/
├── migrations/                  # Versioned schema definitions (framework-native, not raw SQL)
├── seeders/                     # Default rewards, settings, admin roles
└── ERD.md                       # Entity relationship documentation

docs/
├── ARCHITECTURE.md
├── API_CONTRACT.md
├── ADMIN_GUIDE.md
├── DEPLOYMENT.md
└── SECURITY.md

.github/
├── workflows/
│   ├── flutter-apk-build.yml
│   ├── backend-ci.yml
│   └── admin-ci.yml
├── ISSUE_TEMPLATE/
└── pull_request_template.md
```

---

## 3. Module Structure

CashNest is organized into **bounded modules**. Each module exists consistently across the backend (Controller + Service + Repository), the app (feature), and the admin panel (management screen).

### 3.1 Module Map

| # | Module | User App | Backend Service | Admin Panel |
|---|--------|----------|-----------------|-------------|
| 1 | **Auth & Identity** | Google/Email login, session | AuthService, TokenService | — (staff auth separate) |
| 2 | **Wallet & Ledger** | Balance, transactions | WalletService, LedgerService | Wallet Management |
| 3 | **Coins/Currency** | Coin balance & conversion | CurrencyService | Reward Management (rates) |
| 4 | **Daily Check-in** | Streak calendar | CheckinService | Reward Management |
| 5 | **Scratch Card** | Scratch UI + reveal | ScratchService | Reward Management |
| 6 | **Spin Wheel** | Spin UI + result | SpinService | Reward Management |
| 7 | **Offerwall** | Offer list + open | OfferwallService | Offerwall Management |
| 8 | **CPA Offers** | Offer detail + track | CpaService | Offerwall/Ads Management |
| 9 | **Referral** | Invite + track | ReferralService | Referral Management |
| 10 | **Tasks** | Task list + complete | TaskService | Reward Management |
| 11 | **Leaderboard** | Rankings | LeaderboardService | Reports |
| 12 | **Withdrawals** | Request + history | WithdrawService | Withdraw Management |
| 13 | **Profile** | View/edit profile | ProfileService | User Management |
| 14 | **Notifications** | In-app + push inbox | NotificationService | Notifications |
| 15 | **Settings** | Preferences | SettingsService | Settings |
| 16 | **Support** | Tickets/FAQ | SupportService | (Support inbox) |
| 17 | **Ads** | Ad placements | AdsService | Ads Management |
| 18 | **Reporting/Analytics** | — | ReportService | Dashboard + Reports |
| 19 | **Admin/RBAC** | — | AdminAuthService | User/Role Management |

### 3.2 Cross-Cutting Modules (shared by all)

- **Validation** — request schema enforcement.
- **Rate Limiting & Abuse Guard** — per-user/IP throttling on reward endpoints.
- **Fraud Engine** — device fingerprint, VPN/emulator flags, velocity checks before crediting.
- **Ledger/Idempotency** — every money mutation flows through a single guarded path.
- **Audit Log** — every admin action recorded.
- **Notification Dispatcher** — routes events → FCM + in-app inbox.

### 3.3 Module Interaction Flow (example: completing an offer)

```
Offerwall Network
   │ (server postback with signed payload + payout)
   ▼
[Postback Controller] → verify signature/IP → [FraudEngine.check]
   │ pass                                          │ fail → flag, no credit
   ▼
[OfferwallService.recordConversion] (idempotent by transaction_id)
   ▼
[LedgerService.credit] → wallet_transactions (+coins)
   ▼
[Event: WalletCredited] → [ReferralService.commission] (+bonus to referrer)
                        → [NotificationService.push] ("You earned X coins!")
                        → [LeaderboardService.markDirty]
```

### 3.4 Extended Production Modules (v1.1)

These modules harden CashNest for production operations and give operators full runtime control without app redeploys. Like the core modules, each spans backend (Controller + Service + Repository), admin panel (management screen), and — where user-facing — the app.

| # | Module | User App | Backend Service | Admin Panel |
|---|--------|----------|-----------------|-------------|
| 20 | **Multi Ad Network Management** | Renders active network's ad units | AdNetworkService (AdMob / AppLovin MAX / Unity Ads) | Ads Management → Networks |
| 21 | **Payment Gateway Management** | Payout method selection reflects active gateways | PaymentGatewayService | Payment Gateway Management |
| 22 | **Banner Management** | Home/promo banners (dynamic) | BannerService | Banner Management |
| 23 | **Announcement System** | In-app announcement bar / popup | AnnouncementService | Announcement Management |
| 24 | **App Version Management** | Force-update & maintenance gate | AppVersionService | App Version Management |
| 25 | **Remote Configuration** | Live feature flags & values | RemoteConfigService | Remote Config Management |
| 26 | **CMS (Legal & Content)** | Privacy, Terms, About, FAQ pages | CmsService | CMS Management |
| 27 | **Database Backup & Restore** | — | BackupService | Backup & Restore |
| 28 | **Dynamic Home Screen** | Server-driven home layout | HomeLayoutService | Home Screen Builder |
| 29 | **Theme Management** | Runtime theming (colors/logo/mode) | ThemeService | Theme Management |

**Design notes for the extended set:**

- **Multi Ad Network** — a single `AdNetworkService` abstracts three providers (**AdMob, AppLovin MAX, Unity Ads**) behind one interface; the admin toggles which network is active per placement (banner/interstitial/rewarded) and per country/priority, so **enable/disable happens from Admin with no app release**. Rewarded-ad completion still verifies server-side before crediting (ties into Wallet/Ledger).
- **Payment Gateway Management** — abstracts payout/collection gateways (e.g., UPI/Razorpay, PayPal, Paytm, gift-card providers) behind a `PaymentGatewayService`; admin enables/configures credentials and per-gateway limits. Complements the existing Withdraw module (which handles the request lifecycle) by standardizing *how* payouts are executed.
- **Banner, Announcement, CMS, Home Screen, Theme, Remote Config** — all **server-driven content/config** modules. The app fetches them on launch/refresh, enabling live changes to look, messaging, layout, and behavior without publishing a new APK.
- **App Version Management** — centralizes **Force Update** and **Maintenance Mode** (subsumes and formalizes the earlier `/settings/app` gate).
- **Database Backup & Restore** — operational safety module for scheduled and on-demand backups with restore workflow, aligned to shared-hosting constraints (see §12).

---

## 4. Database Planning

**Engine:** MySQL / InnoDB (foreign keys, transactions). Balances are always reconciled through the ledger table. No table stores an editable balance without a corresponding immutable transaction record.

> Listing tables with purpose and relationships only (no schema DDL).

### 4.1 Identity & Users

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `users` | Core end-user account (name, email, avatar, status, referral_code, wallet balances cache). | 1—1 `user_auth_providers`, 1—N most tables. |
| `user_auth_providers` | Links a user to a login method (google / email) and provider identifiers. | N—1 `users`. |
| `user_devices` | Registered devices, FCM tokens, device fingerprint, platform, last_seen. | N—1 `users`. |
| `user_sessions` | Issued refresh tokens / login sessions for revocation. | N—1 `users`. |
| `user_kyc` | Optional identity/payment verification for withdrawals. | 1—1 `users`. |

### 4.2 Wallet & Currency

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `wallets` | Current coin and cash-equivalent balances per user (cached, ledger-backed). | 1—1 `users`. |
| `wallet_transactions` | **Immutable ledger** of every credit/debit with type, source_module, reference_id (idempotency), balance_after. | N—1 `users`, N—1 `wallets`. |
| `currency_settings` | Conversion rates (coins → currency), min/max thresholds. | Referenced globally. |

### 4.3 Reward Mechanics

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `daily_checkins` | Per-user daily check-in records + streak state. | N—1 `users`. |
| `checkin_rewards_config` | Day-wise reward ladder configuration. | Referenced by CheckinService. |
| `scratch_cards` | Issued scratch cards, reward value, revealed/claimed state, expiry. | N—1 `users`. |
| `scratch_card_config` | Prize pool / probability weights for scratch cards. | Referenced by ScratchService. |
| `spin_wheel_segments` | Wheel segment definitions (reward, probability weight, active). | Referenced by SpinService. |
| `spin_history` | Each spin outcome per user + daily-limit tracking. | N—1 `users`, N—1 `spin_wheel_segments`. |
| `tasks` | Task catalog (title, type, reward, requirement, active window). | 1—N `task_completions`. |
| `task_completions` | Which user completed which task, status, credited flag. | N—1 `users`, N—1 `tasks`. |

### 4.4 Offerwall & CPA

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `offerwall_providers` | Configured networks (name, API keys, postback secret, active). | 1—N `offer_conversions`. |
| `offers` | Cached/curated offers (title, payout, category, provider). | N—1 `offerwall_providers`. |
| `offer_clicks` | User click/open tracking for attribution + fraud velocity. | N—1 `users`, N—1 `offers`. |
| `offer_conversions` | Confirmed conversions from postbacks (transaction_id unique = idempotency). | N—1 `users`, N—1 `offerwall_providers`. |
| `cpa_offers` | CPA-specific offer definitions and payout tiers. | N—1 `offerwall_providers`. |
| `postback_logs` | Raw inbound postback audit (payload, IP, signature result). | N—1 `offerwall_providers`. |

### 4.5 Referral

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `referrals` | Referrer→referee link, status (pending/qualified), reward state. | Both FKs → `users`. |
| `referral_config` | Referral reward amounts, qualification rules, commission %. | Referenced globally. |
| `referral_earnings` | Commission entries generated from referee activity. | N—1 `referrals`, N—1 `users`. |

### 4.6 Leaderboard

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `leaderboard_periods` | Defines periods (daily/weekly/monthly/all-time). | 1—N `leaderboard_entries`. |
| `leaderboard_entries` | Computed rank + score snapshot per user per period. | N—1 `users`, N—1 `leaderboard_periods`. |

### 4.7 Withdrawals

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `withdraw_methods` | Supported payout methods (UPI, PayPal, gift card, bank). | 1—N `withdraw_requests`. |
| `withdraw_requests` | User payout requests (amount, method, payment_detail, status, admin_note). | N—1 `users`, N—1 `withdraw_methods`. |
| `withdraw_history` | Finalized payout audit / status transitions (or derived from requests). | N—1 `withdraw_requests`. |

### 4.8 Engagement & System

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `notifications` | In-app notification inbox per user (title, body, type, read state, deep link). | N—1 `users`. |
| `notification_campaigns` | Admin-composed broadcasts (audience, schedule, status). | 1—N `notifications`. |
| `support_tickets` | User support requests (subject, status, priority). | N—1 `users`, 1—N `support_messages`. |
| `support_messages` | Threaded messages within a ticket. | N—1 `support_tickets`. |
| `faqs` | Support FAQ content. | Standalone. |
| `ads_placements` | Ad slots config (banner/interstitial/rewarded, network, active). | Referenced by app. |
| `app_settings` | Global key–value config / feature flags. | Standalone. |
| `user_settings` | Per-user preferences (notif toggles, language). | 1—1 `users`. |

### 4.9 Admin & Governance

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `admins` | Admin/staff accounts. | N—1 `admin_roles`. |
| `admin_roles` | Roles (super_admin, finance, support, moderator). | 1—N `admins`, N—N `admin_permissions`. |
| `admin_permissions` | Granular permissions per module/action. | N—N `admin_roles`. |
| `admin_audit_logs` | Every admin action (actor, action, target, before/after). | N—1 `admins`. |
| `fraud_flags` | Users/events flagged by the fraud engine with reason + status. | N—1 `users`. |

### 4.10 Relationship Summary (ERD narrative)

- `users` is the hub: 1—1 with `wallets`, `user_kyc`, `user_settings`; 1—N with devices, transactions, check-ins, scratch cards, spins, task completions, offer activity, referrals, withdrawals, notifications, tickets.
- `wallet_transactions` is the **financial spine** — every reward/referral/withdrawal writes exactly one ledger row, referenced by `reference_id` for idempotency.
- `offerwall_providers` fans out to offers, conversions, CPA offers, and postback logs.
- `referrals` self-references `users` twice (referrer + referee).
- `admin_roles` ↔ `admin_permissions` is the RBAC many-to-many backbone.

### 4.11 Extended Production Modules (v1.1)

> Tables listed with purpose and relationships only (no schema DDL). All admin-editable config tables are covered by `admin_audit_logs`.

#### Multi Ad Network Management

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `ad_networks` | Registered networks (admob / applovin_max / unity_ads) with credentials/app IDs and enabled flag. | 1—N `ad_units`. |
| `ad_units` | Per-network ad units by type (banner/interstitial/rewarded), status, per-country/priority ordering. | N—1 `ad_networks`, N—1 `ads_placements`. |
| `ad_network_events` | Impression/completion logs for rewarded ads (feeds fraud + reporting). | N—1 `users`, N—1 `ad_units`. |

> Extends the existing `ads_placements`: a placement now resolves to the highest-priority **enabled** unit across networks (mediation waterfall configured in admin).

#### Payment Gateway Management

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `payment_gateways` | Supported gateways (UPI/Razorpay, PayPal, Paytm, gift-card, bank) with credentials, fees, min/max, enabled flag. | 1—N `withdraw_methods`, 1—N `gateway_transactions`. |
| `gateway_transactions` | Execution records for payouts routed through a gateway (status, external_ref, idempotency key). | N—1 `payment_gateways`, N—1 `withdraw_requests`. |

> Links to existing `withdraw_methods`/`withdraw_requests`: the Withdraw module owns the *request*, the gateway module owns the *execution*.

#### Banner Management

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `banners` | Promotional banners (image, title, action_type, deep_link/url, placement, order, active window, target audience). | Referenced by app home/promo. |

#### Announcement System

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `announcements` | In-app announcements (title, body, type: bar/popup, priority, audience, start/end, active). | Referenced by app. |
| `announcement_reads` | Per-user dismissed/seen state (avoids re-showing). | N—1 `users`, N—1 `announcements`. |

#### App Version Management

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `app_versions` | Per-platform version records (version_code, min_supported, force_update flag, changelog, store_url). | Standalone (queried at launch). |
| `maintenance_windows` | Maintenance-mode state (enabled, message, scheduled start/end). | Standalone. |

#### Remote Configuration System

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `remote_configs` | Typed key–value flags/values (bool/int/string/json), environment, audience segment, active. | Standalone (superset of `app_settings`; feature-flag authority). |

#### CMS Module (Legal & Content)

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `cms_pages` | Editable content pages by slug (privacy-policy, terms, about, faq-intro), title, body (HTML/markdown), version, published flag, locale. | Standalone. |
| `faq_categories` | Grouping for FAQ entries. | 1—N `faqs`. |

> Consolidates the existing `faqs` table under CMS; `faqs` now optionally references `faq_categories`.

#### Database Backup & Restore

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `backup_jobs` | Backup records (type: manual/scheduled, status, file path/location, size, checksum, created_by). | N—1 `admins`. |
| `restore_logs` | Restore operation audit (source backup, status, performed_by, timestamps). | N—1 `backup_jobs`, N—1 `admins`. |

#### Dynamic Home Screen Management

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `home_sections` | Ordered home-screen blocks (type: banner_carousel/quick_actions/offers/leaderboard/custom, config json, order, active, audience). | Referenced by app home. |

#### Theme Management

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `themes` | Theme definitions (primary/secondary/accent colors, logo asset, font, light/dark defaults, active). | Referenced by app at launch. |

### 4.12 Relationship Summary Additions (v1.1)

- `ad_networks` → `ad_units` → resolved by `ads_placements` for the mediation waterfall; rewarded events land in `ad_network_events` and feed the Wallet ledger + fraud engine.
- `payment_gateways` executes what `withdraw_requests` authorize, recorded immutably in `gateway_transactions`.
- Content/config tables (`banners`, `announcements`, `home_sections`, `themes`, `cms_pages`, `remote_configs`, `app_versions`) are **server-driven** and consumed by the app on launch/refresh — no code deploy required to change them.
- `backup_jobs` / `restore_logs` are admin-governance tables, audited like all admin actions.

---

## 5. API Planning

**Base URL:** `https://api.cashnest.app/v1`
**Auth:** `Authorization: Bearer <JWT>` unless marked *public*.
**Format:** JSON request/response; standard envelope `{ status, message, data, meta }`.

### 5.1 Auth Module

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/auth/google` | Login/register via Google ID token. | public |
| POST | `/auth/email/register` | Register with email + password. | public |
| POST | `/auth/email/login` | Login with email + password. | public |
| POST | `/auth/email/verify` | Verify email via OTP/link. | public |
| POST | `/auth/password/forgot` | Request password reset. | public |
| POST | `/auth/password/reset` | Reset password with token. | public |
| POST | `/auth/refresh` | Exchange refresh token for new access token. | refresh |
| POST | `/auth/logout` | Revoke current session/device. | user |
| POST | `/auth/device` | Register/update device + FCM token. | user |

### 5.2 Profile Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/profile` | Get current user profile + wallet summary. |
| PUT | `/profile` | Update profile (name, avatar). |
| POST | `/profile/avatar` | Upload avatar image. |
| GET | `/profile/kyc` | Get KYC status. |
| POST | `/profile/kyc` | Submit KYC details. |
| DELETE | `/profile` | Request account deletion. |

### 5.3 Wallet Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/wallet` | Current coin + cash balance. |
| GET | `/wallet/transactions` | Paginated ledger history (filter by type/date). |
| GET | `/wallet/transactions/{id}` | Single transaction detail. |
| GET | `/wallet/conversion` | Current coin→currency conversion rates. |

### 5.4 Daily Check-in Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/checkin/status` | Streak state + today's availability. |
| POST | `/checkin/claim` | Claim today's check-in reward. |
| GET | `/checkin/calendar` | Reward ladder / calendar view. |

### 5.5 Scratch Card Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/scratch/available` | List available/unrevealed cards. |
| POST | `/scratch/{id}/reveal` | Reveal a card (server decides reward). |
| POST | `/scratch/{id}/claim` | Claim revealed reward. |
| GET | `/scratch/history` | Past scratch results. |

### 5.6 Spin Wheel Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/spin/status` | Spins remaining + wheel segments. |
| POST | `/spin` | Perform a spin (server-determined outcome). |
| GET | `/spin/history` | Spin history. |

### 5.7 Tasks Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/tasks` | List active tasks. |
| GET | `/tasks/{id}` | Task detail. |
| POST | `/tasks/{id}/start` | Mark task started (attribution). |
| POST | `/tasks/{id}/complete` | Submit completion for verification. |
| GET | `/tasks/history` | Completed/pending tasks. |

### 5.8 Offerwall & CPA Module

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/offerwall/providers` | Active offerwall providers. | user |
| GET | `/offerwall/offers` | Aggregated/curated offers list. | user |
| GET | `/offerwall/offers/{id}` | Offer detail + tracking URL. | user |
| POST | `/offerwall/offers/{id}/click` | Record click, return redirect URL. | user |
| GET | `/cpa/offers` | CPA offer catalog. | user |
| GET | `/cpa/offers/{id}` | CPA offer detail. | user |
| POST | `/postback/offerwall/{provider}` | **Server-to-server** conversion callback. | signed/IP |
| POST | `/postback/cpa/{provider}` | CPA conversion callback. | signed/IP |

### 5.9 Referral Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/referral` | User's referral code, link, stats. |
| GET | `/referral/list` | List referred users + status. |
| GET | `/referral/earnings` | Referral commission history. |
| POST | `/referral/apply` | Apply a referral code (new user). |

### 5.10 Leaderboard Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/leaderboard` | Rankings for a period (daily/weekly/monthly/all-time). |
| GET | `/leaderboard/me` | Current user's rank + score. |

### 5.11 Withdraw Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/withdraw/methods` | Available payout methods + limits. |
| POST | `/withdraw/request` | Create withdrawal request (debits/holds balance). |
| GET | `/withdraw/history` | Paginated withdrawal history + statuses. |
| GET | `/withdraw/{id}` | Single request detail. |
| POST | `/withdraw/{id}/cancel` | Cancel a pending request. |

### 5.12 Notifications Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/notifications` | Paginated in-app inbox. |
| POST | `/notifications/{id}/read` | Mark one as read. |
| POST | `/notifications/read-all` | Mark all read. |
| GET | `/notifications/unread-count` | Badge count. |

### 5.13 Settings Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/settings` | User preferences + app config/flags. |
| PUT | `/settings` | Update preferences (notif toggles, language). |
| GET | `/settings/app` | Public app config (version, force-update, maintenance). |

### 5.14 Support Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/support/faqs` | FAQ list. |
| GET | `/support/tickets` | User's tickets. |
| POST | `/support/tickets` | Create ticket. |
| GET | `/support/tickets/{id}` | Ticket thread. |
| POST | `/support/tickets/{id}/reply` | Add message. |

### 5.15 Ads Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/ads/placements` | Ad unit config per placement. |
| POST | `/ads/rewarded/complete` | Verify rewarded-ad completion → credit. |

### 5.16 Ad Networks Module (app)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/ads/config` | Active network + unit IDs per placement (AdMob/MAX/Unity, mediation order). |
| POST | `/ads/impression` | Log an impression (optional analytics). |
| POST | `/ads/rewarded/verify` | Server-verify rewarded completion → credit (supersedes `/ads/rewarded/complete`). |

### 5.17 Payment Gateways Module (app)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/payment/gateways` | Active gateways available for payout + limits/fees. |

> Payout requests themselves continue through the Withdraw module (§5.11); gateway selection is surfaced there.

### 5.18 Banners Module (app)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/banners` | Active banners for a placement (home/promo), ordered, audience-filtered. |

### 5.19 Announcements Module (app)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/announcements` | Active announcements for the user (bar/popup). |
| POST | `/announcements/{id}/seen` | Mark announcement seen/dismissed. |

### 5.20 App Version Module (app)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/app/version` | Latest version, min-supported, force-update flag, store URL. | public |
| GET | `/app/maintenance` | Maintenance-mode status + message. | public |

> These formalize/replace the earlier `/settings/app` gate.

### 5.21 Remote Config Module (app)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/config` | Effective remote config / feature flags for the client (segment-aware). | public/user |

### 5.22 CMS Module (app)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/cms/{slug}` | Fetch a content page (privacy-policy, terms, about). | public |
| GET | `/cms/faq` | FAQ entries grouped by category. | public |

### 5.23 Home Layout Module (app)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/home/layout` | Ordered, server-driven home-screen sections + their config. |

### 5.24 Theme Module (app)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/theme` | Active theme (colors, logo, font, light/dark defaults). | public/user |

### 5.25 Admin API — Extended Modules (namespaced `/admin/*`, staff-auth)

In addition to the core admin endpoints, the extended modules expose admin CRUD + toggle endpoints, all RBAC-guarded and audited:

| Module | Representative Admin Endpoints |
|--------|-------------------------------|
| Ad Networks | `GET/POST/PUT /admin/ad-networks`, `PUT /admin/ad-networks/{id}/toggle`, `.../ad-units` CRUD |
| Payment Gateways | `GET/POST/PUT /admin/payment-gateways`, `PUT /admin/payment-gateways/{id}/toggle` |
| Banners | `GET/POST/PUT/DELETE /admin/banners`, reorder |
| Announcements | `GET/POST/PUT/DELETE /admin/announcements` |
| App Version | `GET/PUT /admin/app-versions`, `PUT /admin/maintenance` |
| Remote Config | `GET/POST/PUT/DELETE /admin/remote-configs` |
| CMS | `GET/POST/PUT /admin/cms/pages`, `.../faq` CRUD |
| Backup & Restore | `GET/POST /admin/backups`, `POST /admin/backups/{id}/restore`, `GET /admin/backups/{id}/download` |
| Home Layout | `GET/POST/PUT/DELETE /admin/home-sections`, reorder |
| Theme | `GET/POST/PUT /admin/themes`, `PUT /admin/themes/{id}/activate` |

### 5.26 Admin API — Core Modules (namespaced `/admin/*`, staff-auth)

Grouped endpoints exist for each admin module (Users, Wallet, Rewards, Offerwall, Ads, Withdraw, Referral, Notifications, Reports, Settings) covering list/detail/create/update/status-change/export operations. These are consumed by the server-rendered admin panel and protected by admin session + RBAC (detailed in §6).

---

## 6. Admin Panel Planning

**Type:** PHP MVC, **server-side rendered** (no public API exposure), session-authenticated with CSRF protection and **role-based access control (RBAC)**. Every mutating action is written to `admin_audit_logs`.

### 6.1 Roles & Permissions (RBAC)

| Role | Scope |
|------|-------|
| **Super Admin** | Full access, settings, role management. |
| **Finance** | Wallet + withdrawals + reports. |
| **Moderator** | Users, fraud flags, rewards config. |
| **Support** | Tickets, notifications, read-only user view. |

Permissions are granular (`module.action`, e.g. `withdraw.approve`, `user.ban`) and mapped to roles via the `admin_roles ↔ admin_permissions` many-to-many.

### 6.2 Modules

| Module | Capabilities |
|--------|--------------|
| **Dashboard** | KPIs: DAU/MAU, new users, coins issued, coins redeemed, pending withdrawals, conversion revenue, fraud flags. Charts + date filters. |
| **User Management** | Search/filter users, view profile & wallet & activity, ban/unban, adjust status, view devices, manual note. |
| **Wallet Management** | View any user's ledger, manual credit/debit (reason-logged, audited), reconciliation view, conversion-rate settings. |
| **Reward Management** | Configure check-in ladder, scratch-card prize pool/odds, spin-wheel segments/odds, tasks CRUD, coin values. |
| **Offerwall Management** | Manage providers (keys, postback secrets, IP allowlist), curate offers, view conversions & postback logs. |
| **Ads Management** | Configure ad networks & placements, rewarded-ad values, enable/disable slots. |
| **Withdraw Management** | Queue of requests, approve/reject/mark-paid, attach payout reference, bulk actions, fraud check panel. |
| **Referral Management** | Configure reward/commission rules, view referral trees, detect referral abuse. |
| **Notifications** | Compose broadcasts (segment/all), schedule, send via FCM, view delivery status, templates. |
| **Reports** | Financial (issued vs redeemed), user growth, offer performance, withdrawal reports; CSV export. |
| **Settings** | App config/feature flags, maintenance mode, force-update version, currency thresholds, admin/role management. |

#### Extended Modules (v1.1)

| Module | Capabilities |
|--------|--------------|
| **Ad Network Management** | Register/configure **AdMob, AppLovin MAX, Unity Ads**; set app IDs & unit IDs per placement (banner/interstitial/rewarded); **enable/disable each network**; set mediation priority/waterfall & per-country targeting; view impression/rewarded reports. |
| **Payment Gateway Management** | Add/configure gateways (credentials, fees, min/max), **enable/disable per gateway**, map gateways to payout methods, view gateway transaction logs. |
| **Banner Management** | CRUD banners (image, action/deep-link, placement, order), schedule active windows, target audience, drag-order. |
| **Announcement Management** | Compose bar/popup announcements, set priority/audience/schedule, activate/deactivate, view reach. |
| **App Version Management** | Set latest & minimum-supported version per platform, **toggle Force Update**, edit store URLs & changelog, **toggle Maintenance Mode** with custom message/schedule. |
| **Remote Config Management** | Create/edit typed flags & values, scope by environment/audience segment, activate instantly (feature-flag control). |
| **CMS Management** | Rich-text edit **Privacy Policy, Terms, About, FAQ** (with categories); versioning, publish/unpublish, locale variants. |
| **Backup & Restore** | Trigger on-demand backup, view scheduled backups, download backup files, **restore from a selected backup** (guarded, dual-confirmation), integrity/checksum display. |
| **Home Screen Builder** | Add/reorder/toggle home sections (banner carousel, quick actions, offers, leaderboard, custom), configure each block, target audience — **live dynamic home**. |
| **Theme Management** | Edit colors (primary/secondary/accent), upload logo, choose font, set light/dark defaults, **activate a theme** applied app-wide on next launch/refresh. |

### 6.3 Admin UX & Security Controls

- **Two-factor** login for admin accounts.
- **IP allowlist** option for the admin subdomain.
- **Confirmation + reason** required for money-affecting actions (manual credit, withdrawal approval).
- **Dual-control (optional)** for large withdrawals — one initiates, another approves.
- **Audit trail** viewable per entity ("who changed what, when").
- **Restricted destructive actions (v1.1)** — **Database Restore**, **Ad Network / Payment Gateway credential edits**, and **Maintenance Mode** are limited to **Super Admin**, require re-authentication + explicit confirmation, and are fully audited. Payment/ad-network secrets are write-only in the UI (masked, never displayed after save).

---

## 7. Flutter App Planning

### 7.1 Architecture

**Clean Architecture** with three layers (Presentation → Domain → Data) and a chosen state-management approach (**Bloc or Riverpod**) for predictable, testable state. Dependency injection wires repositories and services.

```
Presentation (Widgets + State) → Domain (UseCases + Entities) → Data (Repositories → API/Local)
```

### 7.2 Feature-to-Screen Map

| Feature | Screens |
|---------|---------|
| **Auth** | Splash, Onboarding, Login (Google + Email), Register, OTP/Verify, Forgot Password. |
| **Home** | Dashboard hub: balance card, daily check-in banner, quick actions (spin, scratch, tasks), promo carousel. |
| **Wallet** | Balance, transaction history (filters), coin↔currency info. |
| **Rewards** | Daily Check-in calendar, Scratch Card grid + reveal animation, Spin Wheel with animation. |
| **Offerwall** | Provider tabs, offer list, offer detail, in-app browser/redirect. |
| **CPA Offers** | Offer catalog, detail, tracking. |
| **Referral** | Invite screen (code, share sheet), referral stats, earnings. |
| **Tasks** | Task list, task detail, completion flow. |
| **Leaderboard** | Period tabs, ranked list, "your rank" highlight. |
| **Withdraw** | Method selection, request form, limits, confirmation. |
| **Withdraw History** | Status timeline per request. |
| **Profile** | View/edit, KYC, avatar. |
| **Notifications** | Inbox list, detail, mark-read. |
| **Settings** | Preferences, notif toggles, language, logout, delete account. |
| **Support** | FAQ, ticket list, ticket thread, new ticket. |
| **Announcements** *(v1.1)* | Announcement bar on home + full-screen popup for high-priority notices. |
| **Legal/CMS** *(v1.1)* | Privacy Policy, Terms, About, FAQ pages rendered from CMS content. |

> **Dynamic surfaces (v1.1):** Home layout, banners, theme, remote config, and the version/maintenance gate are **not hard-coded screens** — they are driven by server responses and rendered generically, so operators change them without an app release.

### 7.3 Cross-Cutting App Concerns

- **Networking:** Central Dio/HTTP client with interceptors for JWT injection, auto token-refresh on 401, retry, and error normalization.
- **Secure Storage:** JWT/refresh tokens in `flutter_secure_storage` (Keystore-backed).
- **State & Cache:** Cached wallet/profile for offline-friendly reads; optimistic UI where safe, but **reward outcomes always confirmed by server**.
- **Push:** FCM integration for foreground/background/terminated states + deep linking to relevant screens.
- **Deep Links:** Referral links, notification taps, offer returns.
- **Force Update & Maintenance:** App checks `/settings/app` on launch and can block with an update/maintenance gate.
- **Analytics:** Firebase Analytics events for funnels (login, earn, withdraw).
- **Localization:** i18n-ready with translation assets.
- **Anti-abuse client signals:** device fingerprint, emulator/root detection hints sent to backend (server remains authoritative).
- **Ad mediation (v1.1):** a single ad abstraction fetches `/ads/config` and initializes the **active** network(s) — **AdMob, AppLovin MAX, or Unity Ads** — per placement; rewarded credits only after server verification via `/ads/rewarded/verify`.
- **Remote config & theme bootstrap (v1.1):** on launch the app fetches `/config`, `/theme`, `/app/version`, and `/app/maintenance` before rendering — applying feature flags, theme, and gating on force-update/maintenance. Values are cached for offline start with a safe fallback theme/config.
- **Dynamic home & banners (v1.1):** home renders from `/home/layout` + `/banners` using a section/widget registry; unknown section types degrade gracefully (forward-compatible).
- **Announcements (v1.1):** fetched from `/announcements`, shown once per user, dismissal synced via `/announcements/{id}/seen`.

### 7.4 App ↔ Backend Contract Rules

- App never computes rewards — it requests actions and renders server results.
- All lists are paginated with cursor/offset + `meta`.
- Every earn action is idempotent from the client's perspective (safe to retry).

---

## 8. Security Architecture

### 8.1 Authentication

- **JWT access tokens** (short-lived, e.g., 15–60 min) + **refresh tokens** (long-lived, rotated, revocable via `user_sessions`).
- **Google login:** backend verifies the Google **ID token signature and audience** server-side — never trusts the client's claim.
- **Email login:** passwords hashed with **bcrypt/argon2**; email verification + rate-limited login.
- **Admin:** separate credential store, session-based auth, **2FA**, CSRF tokens on all forms.

### 8.2 Authorization

- **User scope:** JWT carries user id; every query is scoped to the authenticated user.
- **Admin scope:** RBAC middleware checks `module.action` permission before every controller action.

### 8.3 Financial Integrity (the core risk area)

- **Immutable ledger** — balances derived/validated from `wallet_transactions`.
- **Idempotency keys** — `reference_id` uniqueness prevents double credit from retries/duplicate postbacks.
- **DB transactions** — every debit/credit wrapped in a transaction with row locking to prevent race conditions/double-spend.
- **Server-authoritative reward outcomes** — spin/scratch/check-in results decided server-side; client sends intent only.
- **Withdrawal holds** — requested amount reserved atomically; cannot exceed available balance.

### 8.4 Offerwall/Postback Security

- **Signed postbacks** — verify HMAC/hash signature using per-provider secret.
- **IP allowlisting** — accept postbacks only from known network IPs.
- **Replay protection** — reject duplicate `transaction_id`; log all raw postbacks.
- **Payout validation** — cross-check payout value against configured offer.

### 8.5 Fraud Prevention

- Device fingerprinting + one-account-per-device policy signals.
- Emulator/VPN/root detection flags.
- Velocity checks (too many earns/referrals/withdrawals in a window).
- Referral abuse detection (same device/IP self-referral).
- Auto-flag suspicious accounts to `fraud_flags` and hold withdrawals for review.

### 8.6 Transport & Platform Security

- **HTTPS/TLS everywhere**; HSTS headers.
- **Security headers** (CSP, X-Frame-Options, X-Content-Type-Options) via `.htaccess`.
- **Input validation & output encoding** — prevent SQLi (PDO prepared statements only), XSS, and injection.
- **Rate limiting** on auth and reward endpoints.
- **Secrets in environment** (`.env`, never committed); config outside web root.
- **CORS** locked to known origins.
- **File upload** validation (avatar): type/size checks, stored outside public path or via safe naming.

### 8.7 Privacy & Compliance

- KYC data encrypted at rest where feasible.
- Account deletion flow (data removal/anonymization).
- Minimal PII in logs; audit logs retained per policy.

### 8.8 Security for Extended Modules (v1.1)

- **Ad network & payment credentials** — stored **encrypted at rest**, injected via env/secret store, never returned to the client or shown in the admin UI after save (masked, write-only). Only `/ads/config` exposes *public* unit IDs, never secrets.
- **Rewarded-ad integrity** — client reports completion, but coins are credited only after **server-side verification** (network callback / SSV where supported) with idempotency, closing the fake-reward exploit.
- **Payment gateway execution** — payouts routed server-side only; `gateway_transactions` carry idempotency keys; webhook/callback signatures verified (same discipline as offerwall postbacks in §8.4).
- **Remote config / feature flags** — served read-only to clients; only RBAC-authorized admins mutate them; changes audited. Config cannot alter money math (server remains authoritative regardless of flags).
- **CMS content** — sanitized on save (HTML sanitization) to prevent stored XSS delivered to the app/webviews.
- **App Version / Maintenance gate** — evaluated server-side; maintenance mode returns a hard gate so no reward/withdraw endpoints process during maintenance.
- **Database Backup & Restore** — backups stored **off the public web root** and encrypted; download/restore restricted to Super Admin with re-authentication and full audit; restore requires explicit dual-confirmation to prevent accidental data loss. Backups exclude secrets or store them encrypted.
- **Banners / Home layout / Announcements** — action URLs and deep links validated against an allowlist scheme to prevent open-redirect / malicious deep-link injection.
- **Theme assets** — uploaded logo/images validated (type/size) and served from safe paths.

---

## 9. Firebase Integration Plan

### 9.1 Services Used

| Firebase Service | Purpose |
|------------------|---------|
| **Firebase Cloud Messaging (FCM)** | Push notifications (transactional + campaigns). |
| **Google Sign-In (via Firebase Auth or Google Identity)** | Google login on the app. |
| **Firebase Analytics** *(optional)* | Engagement/funnel analytics. |
| **Firebase Crashlytics** *(optional)* | Crash reporting. |

### 9.2 FCM Flow

```
[Event in backend]  (e.g., WalletCredited, WithdrawApproved, Campaign)
        │
        ▼
[NotificationService] → writes to `notifications` (in-app inbox)
        │
        ▼
[FCM Dispatcher (PHP)] → FCM HTTP v1 API (OAuth2 via service account)
        │  targets device tokens from `user_devices`
        ▼
[Google FCM]  →  Android device  →  app foreground/background/terminated handlers
        │
        ▼  (tap)
[Deep link] → relevant app screen
```

### 9.3 Token Lifecycle

- On login/app-start, app registers its **FCM token** via `POST /auth/device`.
- Tokens stored in `user_devices`; refreshed tokens update the record.
- Invalid/expired tokens (FCM error responses) are pruned automatically.

### 9.4 Notification Types

- **Transactional:** reward earned, withdrawal status change, referral qualified.
- **Engagement:** daily check-in reminder, new offers, "spin available".
- **Campaigns:** admin-composed broadcasts (segmented or all-users), schedulable.

### 9.5 Backend Configuration

- Firebase **service account JSON** stored securely outside web root, referenced via env.
- FCM sends batched by device-token chunks (shared-hosting friendly, invoked via cron for large campaigns).
- Delivery status/logging recorded for campaign reporting.

### 9.6 App Configuration

- `google-services.json` in `mobile/android/app/` (kept in repo or injected at build time via GitHub Secrets).
- Foreground message handler shows in-app; background handled by system tray.
- Notification channels configured (transactional vs promotional) for Android.

---

## 10. GitHub Repository Structure

**Strategy:** Single **monorepo** (`cashnest`) containing backend, admin, mobile, database, docs, and CI — simplifies coordinated changes and shared contracts.

```
cashnest/
├── backend/              # PHP MVC REST API
├── admin/                # PHP admin panel
├── mobile/               # Flutter app
├── database/             # migrations, seeders, ERD
├── docs/                 # architecture & guides
├── scripts/              # deploy / cron helpers
├── .github/
│   ├── workflows/
│   │   ├── flutter-apk-build.yml
│   │   ├── backend-ci.yml
│   │   └── admin-ci.yml
│   ├── ISSUE_TEMPLATE/
│   │   ├── bug_report.md
│   │   └── feature_request.md
│   ├── pull_request_template.md
│   └── CODEOWNERS
├── .gitignore
├── .editorconfig
├── LICENSE
└── README.md
```

### 10.1 Branching Strategy

| Branch | Purpose |
|--------|---------|
| `main` | Production-ready, protected, tagged releases. |
| `develop` | Integration branch for features. |
| `feature/*` | Individual features. |
| `fix/*` | Bug fixes. |
| `release/*` | Release stabilization. |
| `hotfix/*` | Urgent production fixes. |

### 10.2 Repo Governance

- **Branch protection** on `main` (required reviews + passing CI).
- **CODEOWNERS** per directory (backend / mobile / admin owners).
- **PR template** enforcing description, testing notes, screenshots.
- **Conventional commits** for readable history + automated changelogs.
- **Secrets** never committed — managed via **GitHub Secrets** (keystore, service accounts, DB creds for deploy).
- `.gitignore` excludes `vendor/`, `.env`, build outputs, keystores, `google-services.json` (if injected).

### 10.3 Environments (GitHub Environments)

- `staging` and `production` environments with protected secrets and optional required reviewers for deploy.

---

## 11. GitHub Actions Strategy

### 11.1 Workflows Overview

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| **flutter-apk-build.yml** | push/tag on `mobile/**`, manual dispatch, release tag | Build & sign Android APK/AAB, upload artifact. |
| **backend-ci.yml** | push/PR on `backend/**` | Lint (PHPCS), static analysis (PHPStan), run PHPUnit. |
| **admin-ci.yml** | push/PR on `admin/**` | Lint + tests for admin panel. |
| **deploy.yml** *(optional)* | push to `main` / manual | Deploy backend+admin to shared hosting via FTP/SSH. |

### 11.2 Flutter APK Build Pipeline

```
Trigger (push/tag/manual)
   │
   ▼
Checkout → Setup Java (JDK) → Setup Flutter → flutter pub get
   │
   ▼
Analyze (flutter analyze) → Test (flutter test)
   │
   ▼
Inject secrets:
   • decode keystore from GitHub Secret (base64)
   • write google-services.json from Secret
   • write key.properties / signing config
   │
   ▼
Build release:
   • flutter build apk --release   (and/or --split-per-abi)
   • flutter build appbundle --release
   │
   ▼
Upload artifacts (APK/AAB) → attach to GitHub Release (on tag)
   │
   ▼
(optional) Notify / distribute (Firebase App Distribution / internal channel)
```

**Secrets used:** `ANDROID_KEYSTORE_BASE64`, `ANDROID_KEYSTORE_PASSWORD`, `ANDROID_KEY_ALIAS`, `ANDROID_KEY_PASSWORD`, `GOOGLE_SERVICES_JSON`.

### 11.3 Backend/Admin CI Pipeline

```
Checkout → Setup PHP + Composer → composer install
   │
   ▼
Lint (PHP_CodeSniffer) → Static analysis (PHPStan/Psalm)
   │
   ▼
Unit + Feature tests (PHPUnit) against a MySQL service container
   │
   ▼
Report status → block PR merge on failure
```

### 11.4 Deployment (Shared Hosting)

- Shared hosting typically lacks SSH/CI runners, so deployment uses **FTP/SFTP action** or **SSH rsync** (if available) from `deploy.yml`.
- Steps: build/prepare artifacts → run `composer install --no-dev` → sync `backend/` and `admin/` (excluding dev files) → run migrations via a protected web/cron endpoint.
- **Zero-downtime consideration:** deploy to a temp dir then swap, where hosting allows.
- Cron jobs configured once in hosting panel; workflow only updates code.

### 11.5 Quality Gates

- All PRs require green CI (lint + static analysis + tests).
- APK builds are reproducible and artifact-versioned.
- Release tags produce signed, downloadable builds.

---

## 12. Future Scalability Plan

### 12.1 Migration Path Beyond Shared Hosting

The architecture is built to **outgrow shared hosting gracefully**:

| Stage | Trigger | Move |
|-------|---------|------|
| **1. Shared hosting** | Launch / low traffic | Current design. |
| **2. VPS / Cloud VM** | Cron limits, CPU pressure | Same codebase on a VPS with real workers + Redis. |
| **3. Managed cloud** | Sustained growth | Containerize (Docker), load balancer, managed MySQL. |
| **4. Horizontal scale** | High concurrency | Multiple stateless API nodes behind LB; DB read replicas. |

### 12.2 Performance & Caching

- Introduce **Redis** for: rate limiting, leaderboard sorted-sets, session/token blacklist, hot config, and job queues.
- **Query optimization & indexing** on high-traffic tables (`wallet_transactions`, `offer_conversions`, `leaderboard_entries`).
- **CDN** for static admin assets and app images.
- Response caching for public config/offers.

### 12.3 Asynchronous Processing

- Replace cron-driven tasks with a **real queue/worker** (e.g., Redis + Supervisor, or a managed queue) for FCM campaigns, postback processing, and leaderboard recomputation — enabling near-real-time crediting at scale.

### 12.4 Database Scaling

- **Read replicas** for reporting/leaderboard reads.
- **Partitioning/archival** of `wallet_transactions` and `postback_logs` by time.
- Move analytics to a **separate reporting store / data warehouse** to keep the transactional DB lean.

### 12.5 Service Extraction (selective)

- Extract high-load, independently-scaling concerns (Notifications, Offerwall postback ingestion, Fraud engine) into **dedicated services/microservices** once the monolith is proven — the module boundaries already defined make this a clean cut.

### 12.6 Platform Expansion

- **iOS app** — the Clean Architecture + shared API contract makes adding an iOS Flutter target straightforward.
- **Multi-currency / multi-region** — currency and thresholds are already config-driven.
- **New reward mechanics** — pluggable via the reward-config tables without schema overhaul.
- **New offerwall/CPA networks** — provider-driven design supports adding networks via admin config.

### 12.7 Observability at Scale

- Centralized logging (ELK/hosted), metrics (Prometheus/Grafana), error tracking (Sentry), and uptime monitoring.
- Financial reconciliation dashboards and automated anomaly alerts on coin issuance vs redemption.

### 12.8 Reliability

- Automated DB backups + tested restore.
- Feature flags for safe rollout.
- Blue-green / canary deploys once on cloud infrastructure.

### 12.9 Scalability of Extended Modules (v1.1)

- **Server-driven content/config** (banners, home layout, theme, remote config, CMS, announcements, app version) is **read-heavy and cache-friendly** — front with CDN/edge caching and short-TTL response caching, with cache-busting on admin publish. This keeps launch-time fetches cheap even at high DAU.
- **Ad mediation** — network priority/waterfall lives in DB so new networks are added by config, not code; impression/event logging (`ad_network_events`) is high-volume and should move to the async queue + time-partitioned/archived storage as traffic grows (like `postback_logs`).
- **Payment gateways** — the gateway abstraction lets new providers be added without touching the Withdraw flow; `gateway_transactions` partitioned/archived by time; execution moved to async workers for retries/backoff at scale.
- **Database Backup & Restore** — on shared hosting, use scheduled cron `mysqldump`-style exports to off-server/object storage; after cloud migration, switch to managed automated snapshots + point-in-time recovery. Backup files never live in the public web root.
- **Feature flags as a scaling lever** — Remote Config enables gradual rollout, kill-switches, and A/B testing of new reward mechanics without redeploys, reducing release risk as the user base grows.
- **Content versioning** — CMS/theme/home-layout versioning supports safe rollback of operator changes independent of app releases.

---

## Appendix A — Data Flow: End-to-End Earn → Withdraw

```
1. User logs in (Google/Email) → JWT issued, device+FCM token registered.
2. User earns via check-in / spin / scratch / task / offerwall / referral.
      → Server validates, fraud-checks, writes ONE ledger row (idempotent), updates wallet cache.
      → Event fires: push notification + leaderboard update + referral commission.
3. Balance grows; user views transactions (read from ledger).
4. User requests withdrawal → amount reserved atomically, request queued.
5. Admin reviews (fraud panel) → approves/pays → ledger debit finalized, status updated.
6. User notified via FCM; withdrawal history reflects final state.
```

## Appendix B — Non-Functional Requirements Summary

| NFR | Target |
|-----|--------|
| Security | JWT + RBAC + signed postbacks + ledger integrity. |
| Availability | Best-effort on shared hosting; HA on cloud migration. |
| Performance | Paginated APIs, indexed queries, cache-ready. |
| Maintainability | Layered MVC + Clean Architecture + monorepo + CI. |
| Auditability | Immutable ledger + admin audit logs. |
| Portability | Env-driven config, cloud-migration-ready. |

---

*End of Software Architecture Document — CashNest v1.0*
