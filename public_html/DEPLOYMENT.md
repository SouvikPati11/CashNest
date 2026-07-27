# CashNest — Hostinger Deployment (public_html-root model, File Manager only)

The backend now deploys **directly as the web root**. The front controller
(`index.php`) lives at the top of this folder, so the contents of this
directory ARE your `public_html/`. No subdirectory, no document-root change, no
Composer, no SSH, no terminal.

---

## 1. What to upload

Upload the **contents of this folder** into your Hostinger `public_html/` so the
result looks like:

```
public_html/
├── index.php            ← front controller (was public/index.php)
├── .htaccess            ← routing + blocks the folders below
├── .env                 ← your config (create from .env.example)
├── app/  core/  config/  routes/  database/  resources/  bin/  storage/
├── vendor/              ← committed; no composer install needed
├── _diagnostic.php      ← temporary self-check (delete after)
├── _phpcheck.php        ← temporary PHP probe (delete after)
└── servercheck.txt      ← temporary static probe (delete after)
```

The API answers at the **bare domain**:

```
https://your-domain/            -> API info
https://your-domain/v1/health   -> health check
https://your-domain/admin/login -> admin panel
```

The framework folders (`app`, `core`, `config`, `database`, `routes`, `storage`,
`vendor`, `tests`, `bin`, `resources`) and `.env` sit under the web root but are
blocked from direct HTTP access by the `.htaccess` here.

---

## 2. One-time steps (File Manager + hPanel only)

1. **Upload** this folder's contents into `public_html/`.
2. **`.env`** — copy `.env.example` to `.env` and fill in DB credentials, JWT
   secret, etc. (edit right in File Manager).
3. **Database** — import `database/cashnest.sql` in phpMyAdmin (61 tables).
4. **Permissions** (File Manager → select all → Permissions, apply recursively):
   - Files **644**, folders **755**. Never **777/666** — LiteSpeed's suEXEC
     returns 500 for group/other-writable scripts.
5. **PHP** — hPanel → PHP Configuration → 8.x, click **Save**. Ensure
   `json, pdo, pdo_mysql, mbstring, openssl` are enabled.

That's the whole deployment.

---

## 3. No mobile-app changes

Routes and the JSON response format are unchanged. Because the API is served at
the domain root, the app's normal base URL (`https://your-domain/v1/...`)
resolves directly — the existing APK works without rebuilding.

---

## 4. If a request returns HTTP 500 (Hostinger hides the real error)

LiteSpeed replaces 500 bodies with its own page, so use these File-Manager probes
(all return HTTP 200, which the host does not mask):

| Open in browser | If 500 | If OK |
|-----------------|--------|-------|
| `/servercheck.txt` (static) | Server/`.htaccess` level — no PHP runs for text. See §5. | Static serving + `.htaccess` parse fine. |
| `/_phpcheck.php` (bare PHP) | PHP handler or file **permissions** (redo §2.4; check PHP version §2.5). | PHP executes; any 500 is in-app → next row. |
| `/_diagnostic.php` (set `APP_DEBUG=true`) | — | Full self-check: PHP, extensions, autoloader, `.env`, storage writability, live DB connection. |

`index.php` also writes boot failures to `storage/logs/deploy-error.log` and, when
`APP_DEBUG=true`, prints the real error as HTTP 200 text.

Delete `servercheck.txt`, `_phpcheck.php`, `_diagnostic.php` and set
`APP_DEBUG=false` once healthy.

---

## 5. `.htaccess` / LiteSpeed notes

The `.htaccess` here uses **only `<IfModule>`-guarded** `mod_rewrite`/`mod_headers`
directives. It contains no `Options`, `DirectoryIndex`, `Require`, `php_value`,
`php_flag`, or `AddHandler` — those need an AllowOverride class the account may
not grant, which makes LiteSpeed reject the whole file with 500 (the classic
"even `echo "Hello"` returns 500"). If `/servercheck.txt` still 500s, rename
`.htaccess` → `htaccess.off` in File Manager and reload; if that clears it, your
account forbids `RewriteEngine` in `.htaccess` — contact Hostinger to enable
`mod_rewrite` overrides for the domain (standard on Hostinger).

To set PHP ini values without `php_value`, create `public_html/.user.ini`:

```
display_errors = Off
upload_max_filesize = 8M
post_max_size = 8M
```

---

## 6. Cron (optional)

hPanel → Advanced → Cron Jobs:

```
* * * * * /usr/bin/php /home/USER/public_html/bin/cron.php >> /dev/null 2>&1
```

---

## 7. Checklist

- [ ] Contents of this folder uploaded into `public_html/`.
- [ ] `.env` created and filled; `database/cashnest.sql` imported.
- [ ] Permissions: files 644, dirs 755 (no 777/666).
- [ ] hPanel: PHP 8.x saved; required extensions on.
- [ ] `https://your-domain/v1/health` returns JSON.
- [ ] `APP_DEBUG=false`; probe files (`_diagnostic.php`, `_phpcheck.php`, `servercheck.txt`) deleted.
