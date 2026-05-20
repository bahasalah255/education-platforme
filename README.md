Lightweight Education Platform (PHP + MySQL)

Quick start

1. Install a LAMP stack (Linux, Apache/Nginx, PHP 8+, MySQL/MariaDB).
2. Copy this project into your web root or configure your virtual host to point to the `public/` folder.
3. Create a database and import `sql/schema.sql` and `sql/seed.sql`.
4. Edit `src/config.php` to set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
5. Open the site in a browser. Login with seed admin: `admin` / `password`.

Files
- `public/` — web entrypoints (index, login, lesson)
- `src/config.php` — DB connection
- `src/auth.php` — authentication helpers
- `sql/` — DB schema and seed
- `assets/` — CSS and JS

Next steps
- Add teacher admin UI to create modules and exercises.
- Improve styling and accessibility.
