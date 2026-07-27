# CashNest — Hostinger Shared Hosting Deployment Audit (LiteSpeed, PHP 8.3)

This is a File-Manager-only deployment guide and a server-level 500 audit. No
SSH, Composer, or terminal is required at any step.

---

## 0. The single most important fact

> **A bare `<?php echo "Hello"; exit;` returns HTTP 500.**

That eliminates the entire application — autoloader, `.env`, routing, database,
and all PHP code. When a script whose only job is to print "Hello" still 500s,
the failure happens **before your PHP script runs**, at one of these layers:

1. The web server rejects an `.htaccess` directive → 500 for every request in
   that directory (even static files and `echo "Hello"`).
2. The PHP handler / suEXEC refuses to execute the file (wrong **permissions**
   or **ownership**) → 500 for every `.php` in that path.
3. An account-level `auto_prepend_file` / handler / ModSecurity rule fires.

So do **not** debug PHP code yet. Isolate the layer first (§2), then apply the
matching fix.

---

## 1. Two-minute isolation probes (upload, open in browser)

Two probe files ship in `public/`:

| URL | What a result means |
|-----|---------------------|
| `…/backend/public/servercheck.txt` | **Static** file, no PHP. |
| `…/backend/public/_phpcheck.php` | Smallest possible PHP, no framework. |

Open them in this order:

- **`servercheck.txt` → 500** → the cause is **server/.htaccess level** (a
  rejected `.htaccess` directive in the path, or an account/handler problem).
  Serving text runs no PHP, so PHP code is not involved. → Go to **§2**.
- **`servercheck.txt` → 200** but **`_phpcheck.php` → 500** → the `.htaccess`
  parses fine; the problem is the **PHP handler or `.php` file permissions**. →
  Go to **§3** and **§4**.
- **Both 200** → the server layer is healthy; the 500 is inside the framework →
  set `APP_DEBUG=true` and open `_phpcheck.php`'s sibling `_diagnostic.php`
  (see §7). This is no longer a "before PHP executes" problem.

Delete both probe files when finished.

---

## 2. `.htaccess` — the #1 cause of "even echo Hello 500s" on LiteSpeed

LiteSpeed parses **every** `.htaccess` in the path to the requested file
(`public_html/.htaccess`, `public_html/backend/.htaccess`,
`public_html/backend/public/.htaccess`). A single directive the account is not
allowed to override makes it reject the whole file with **500 — for every
request in that directory, PHP or not**.

### Directives that require an AllowOverride class (and 500 if it is not granted)

| Directive | Needs AllowOverride | Notes |
|-----------|--------------------|-------|
| `Options …` (e.g. `Options -Indexes`) | `Options` | **Most common Hostinger 500.** |
| `DirectoryIndex …` | `Indexes` | |
| `Require …`, `<FilesMatch>…Require…>` | `AuthConfig` or `Limit` | |
| `php_value` / `php_flag` | `Options`/handler | Use `.user.ini` instead (§5). |
| `AddHandler` / `SetHandler` / `AddType` | `FileInfo` | A stale PHP handler here 500s all `.php`. |
| `RewriteEngine …` and other `mod_rewrite` | `FileInfo` | Guarded by `<IfModule>` but still needs FileInfo. |

**This repository's `.htaccess` files have been reduced to only `<IfModule>`-
guarded `mod_rewrite`/`mod_headers` directives** — the previous unguarded
`Options -Indexes` and `Require all denied` (both parse-time 500 risks) were
removed. Re-upload the updated `backend/.htaccess` and `backend/public/.htaccess`.

### Bisection (File Manager only) — proves whether `.htaccess` is the cause

1. In `public_html/backend/public/`, rename `.htaccess` → `htaccess.off`.
2. Reload `…/backend/public/servercheck.txt` and `_phpcheck.php`.
   - **500 gone** → `public/.htaccess` was the culprit. Put the shipped minimal
     version back; if it 500s, your account forbids `RewriteEngine` in
     `.htaccess` → use **§6** (set the document root to `public`), which needs no
     `.htaccess` at all.
   - **Still 500** → also rename `public_html/backend/.htaccess` → `htaccess.off`,
     then (if still 500) check for a `public_html/.htaccess` and rename it too.
3. When the 500 disappears, the last file you renamed is the offender. Reintroduce
   its directives block by block (each `<IfModule>…</IfModule>` at a time) to find
   the exact rejected line.

> **MultiViews:** `Options -MultiViews` is sometimes needed so LiteSpeed does not
> content-negotiate `/index` to `/index.php`, but because `Options` itself can
> 500, do **not** add it in `.htaccess`. If MultiViews causes route ambiguity,
> disable it in **hPanel → Advanced → (Apache) MultiViews** or by setting the
> document root to `public/` (§6), not via `.htaccess`.

---

## 3. File & directory permissions / ownership — the #1 cause of "`.php` 500s but text is fine"

LiteSpeed runs PHP through **suEXEC/LSAPI**, which **refuses to execute any
script that is writable by group or others, or that is not owned by your hosting
user** — returning **HTTP 500** ("file is writeable by group" in the internal
log you cannot see). This is the classic reason `echo "Hello"` 500s while a
`.txt` may or may not.

Fix in **File Manager → Permissions** (right-click → Permissions / CHMOD):

| Target | Correct value | Never |
|--------|---------------|-------|
| PHP files (`index.php`, `*.php`) | **644** | 666, 664, 646, 777, 755-with-group-write |
| `.htaccess`, `.env`, `*.txt` | **644** | 666, 777 |
| Directories (`backend`, `public`, `core`, `vendor`, `storage`, …) | **755** | 775, 777 |
| `storage/` and its subdirs (needs PHP writes) | **755** (dir) | 777 |

Notes:
- **777 on a directory or 666 on a file is the trigger** — set them to 755/644.
- **Ownership:** files must belong to your hosting account user. If you extracted
  a ZIP or moved files with another tool, ownership can be wrong. The reliable
  File-Manager fix is to **re-upload the file/folder through File Manager itself**
  (which writes it as your user), or use hPanel's "Fix File Ownership" tool if
  present (hPanel → Advanced → *Fix file ownership* / *File permissions*).
- After fixing, re-open `_phpcheck.php`.

---

## 4. PHP version & handler

- In **hPanel → Advanced → PHP Configuration**, confirm the version for **this
  domain** is 8.x and click **Save** even if it already shows 8.3 (this rewrites
  the handler). A domain can silently run a different default than the account.
- Do **not** put `AddHandler application/x-httpd-*` lines in `.htaccess`; a stale
  handler string there 500s every `.php`. Remove any you added.
- `_phpcheck.php` prints the running version and SAPI so you can confirm.
- `composer.json` now requires PHP **>= 8.1** (the code uses no 8.3-only syntax),
  and `vendor/composer/platform_check.php` matches, so a slightly older PHP no
  longer hard-500s inside the autoloader.

---

## 5. `php_value` / `php_flag` / `auto_prepend_file`

- LiteSpeed on Hostinger does **not** reliably accept `php_value`/`php_flag` in
  `.htaccess`; they can 500. This repo's `.htaccess` uses none. To set PHP ini
  values, create **`public_html/backend/public/.user.ini`** (plain `key = value`
  lines), e.g.:
  ```
  display_errors = Off
  upload_max_filesize = 8M
  post_max_size = 8M
  ```
- Check **hPanel → PHP Configuration → PHP options** for a stray
  `auto_prepend_file` / `auto_append_file` pointing at a missing path — that
  500s every request. Clear it if set.

---

## 6. Recommended layout: point the document root at `public/`

Your app is at `public_html/backend/` and the domain root is `public_html/`, so
you must use `…/backend/public/…` and rely on `.htaccess` rewrites. The clean,
more robust option (a single hPanel setting — **not** a reinstall, **not** a
terminal command):

**hPanel → Websites → your domain → set the document/public root to
`public_html/backend/public`.**

Then:
- The API answers at the bare domain (`https://…/v1/health`), no `/backend/public`
  prefix, and no forwarding `.htaccess` is needed.
- `core/`, `app/`, `vendor/`, `.env` sit **above** the web root and are
  unreachable over HTTP.

The application already strips its own base directory from the request path, so
it works **either** way — at the domain root **or** under `/backend/public`.

---

## 7. Seeing the real error once PHP runs (LiteSpeed hides 500 bodies)

Hostinger replaces a 500 response body with its own page, so `display_errors`
looks like it "does nothing". Two mechanisms in this repo work around that:

- **`public/index.php`** catches boot failures and, when `APP_DEBUG=true`, prints
  them as **HTTP 200 text** (LiteSpeed does not replace a 200 body). It also
  writes them to `storage/logs/deploy-error.log`, readable in File Manager.
- **`public/_diagnostic.php`** (set `APP_DEBUG=true`) prints a full self-check:
  PHP version, extensions, autoloader, `.env` keys, storage writability, and a
  live database connection test — always as HTTP 200.

Set `APP_DEBUG=false` and delete `_diagnostic.php`, `_phpcheck.php`, and
`servercheck.txt` once the site is healthy.

---

## 8. Required PHP extensions

Enable in **hPanel → PHP Configuration → PHP extensions** (all are standard on
Hostinger): `json`, `pdo`, `pdo_mysql`, `mbstring`, `openssl`. `_phpcheck.php`
and `_diagnostic.php` list which are loaded.

---

## 9. Quick checklist

- [ ] Upload updated `backend/.htaccess`, `backend/public/.htaccess`, `public/index.php`, probes.
- [ ] Open `servercheck.txt` and `_phpcheck.php` → isolate the layer (§1).
- [ ] Permissions: files 644, dirs 755; no 777/666; correct owner (§3).
- [ ] hPanel: PHP 8.x for this domain, Save; no stray handler/prepend (§4, §5).
- [ ] `.htaccess` bisection if `servercheck.txt` 500s (§2).
- [ ] Prefer document root = `backend/public` (§6).
- [ ] `APP_DEBUG=true` → read the real error (§7) → fix → `APP_DEBUG=false`, delete probes.
