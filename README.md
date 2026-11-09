# Art on Denim (AOD)

Premium PHP + MySQL e-commerce platform for **Art on Denim** — streetwear and designer fashion.

## Requirements

- PHP 8.0+ (PHP 8.1+ recommended)
- MySQL 5.7+ / MariaDB 10.3+
- Apache (XAMPP or WAMP) with `mod_rewrite` preferred
- Modern browser

## Local setup

1. Copy / use `art-on-denim` in your web root (`C:\wamp64\www\art-on-denim` or XAMPP `htdocs`).
2. Start Apache + MySQL.
3. Import `database/art_on_denim.sql` in phpMyAdmin.
4. If upgrading an older DB, also import `database/migrations/001_api_tokens.sql`.
5. Confirm `config/database.php` credentials.
6. Open [http://localhost/art-on-denim/](http://localhost/art-on-denim/)

### Demo accounts

| Role     | Email                   | Password      |
|----------|-------------------------|---------------|
| Admin    | admin@artondenim.co.za  | Password123!  |
| Customer | lebo@example.com        | Password123!  |

**Change these passwords before any shared/hosted deployment.**

- Storefront: `/art-on-denim/`
- Admin: `/art-on-denim/admin/login.php`

## Feature checklist

Customer: Register → Login → Browse/Search/Filter → Product → Wishlist → Cart → Checkout → Confirmation → Orders → Track → Notifications → Profile

Admin: Login → Dashboard → Products/Categories/Brands → Inventory → Customers → Orders (status/tracking) → Promotions → Reports → Messages → Settings

## API (Android)

Base: `/art-on-denim/api/` — Bearer tokens; never connect Android apps to MySQL directly. See endpoint table in earlier README section or call `auth.php?action=login` with JSON.

## Security notes (Phases 22+)

- PDO prepared statements; `htmlspecialchars` via `e()`
- CSRF on browser forms; API uses hashed bearer tokens
- Login rate limiting (5 attempts / 15 min per email+IP)
- Session regeneration on login; admin role re-checked from DB
- Secure cookies when HTTPS; uploads validated (MIME + `getimagesize`); PHP execution denied in `/uploads`
- Promo/CTA URLs restricted to relative app paths
- Set `APP_ENV` to `production` in `config/config.php` before going live (disables debug + mock card auto-pay)

## Responsive

Layouts target 320 / 375 / 390 / 768 / 1024 / 1440+ with mobile nav, stacked checkout, and touch-friendly controls.

## Project status

Phases **1–24** complete for the summative e-commerce platform scope.
