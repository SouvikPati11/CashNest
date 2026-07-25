# Database

Holds versioned schema **migrations** and **seeders** for CashNest.

- `migrations/` — schema definitions authored per `DATABASE_DESIGN.md` (added with feature modules; empty at the foundation stage).
- `seeders/` — default data (settings, admin roles, reward config).

> No SQL/DDL is included at the foundation stage. The `Database\` namespace is autoloaded (PSR-4) so migration/seeder classes can live here later.
