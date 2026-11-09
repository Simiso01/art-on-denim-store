# Art on Denim — Phase 1 analysis and Task 2 checklist

Date: 13 October 2026

## Source of truth

No separate Task 1 document, assignment brief, or lecturer feedback file is in this workspace. The opened project is the Task 1 system: **Art on Denim (AOD)**, a PHP and MySQL streetwear and designer-fashion store.

This checklist treats that codebase, schema, and README as the project identity. It does not replace AOD with a different product.

If a written Task 1 report or lecturer comments are uploaded later, this checklist must be revised against them before submission.

## Project identity

| Item | Finding |
| --- | --- |
| Name | Art on Denim (AOD) |
| Purpose | Premium streetwear and designer fashion e-commerce |
| Market | South Africa (ZAR, VAT 15%, Africa/Johannesburg) |
| Target users | Customers who browse, buy, track orders, and manage a profile; staff who run the admin console |
| Stack already chosen | PHP 8+, MySQL/MariaDB, Apache, session auth, JSON API with hashed bearer tokens |
| Android app | Not present |
| Git | Repository is not initialised |
| WampServer | Not installed (`C:\wamp64` missing; `php` is not on PATH) |
| Android Studio | Installed (`AndroidStudio2026.2.1`, SDK platform `android-37.0`) |
| Java on PATH | Not available in this shell |

## What already exists

### Customer website

Home, shop, product, search, categories, brands, new arrivals, sale, cart, checkout, order confirmation, orders, order details, tracking, wishlist, notifications, account, profile and address settings, register, login, logout, about, contact, 404.

### Admin website

Dashboard, products (add/edit), categories, brands, inventory, customers, customer details, orders, order details, promotions, reports, messages, settings, admin login and logout.

### API already suitable for Android

| Endpoint | Role |
| --- | --- |
| `api/auth.php` | Register, login, logout, current user |
| `api/products.php` | List, filter, search, detail |
| `api/cart.php` | Cart |
| `api/wishlist.php` | Wishlist |
| `api/orders.php` | Orders |
| `api/tracking.php` | Tracking |
| `api/notifications.php` | Notifications |

Passwords use `password_hash()` / `password_verify()` (`PASSWORD_DEFAULT`, bcrypt `$2y$`). Sessions regenerate on login. Browser forms use CSRF. API tokens are hashed. Login attempts are rate-limited in the web auth path.

### Design system already defined

Colours: ink `#0c1016`, denim `#121820`, chalk `#f2efe9`, brass `#b8956c`. Type: Syne and Outfit. Shared in `assets/css/style.css`. The Android app must reuse these tokens. The website still needs a substantial visual upgrade inside this identity.

## Database

Tables: `users`, `categories`, `brands`, `products`, `product_images`, `inventory`, `cart`, `cart_items`, `wishlist`, `orders`, `order_items`, `payments`, `promotions`, `notifications`, `contact_messages`, `order_status_history`, `api_tokens`.

Foreign keys and indexes are already defined.

Seed counts in `database/art_on_denim.sql` (assignment asks for at least 10 appropriate rows per required table):

| Table | Seeded rows | Gap |
| --- | --- | --- |
| users | 3 | Need 7 more realistic users. Do not invent extra plaintext passwords in docs. |
| categories | 6 | Need 4 more |
| brands | 6 | Need 4 more |
| products | 12 | Meets minimum |
| product_images | 16 | Meets minimum; several paths point at JPG files that are not in `assets/images/products` (only SVG placeholders exist) |
| inventory | 50+ | Meets minimum |
| promotions | 3 | Need 7 more |
| cart, cart_items, wishlist, orders, order_items, payments, notifications, contact_messages, order_status_history | 0 | Need at least 10 linked rows each |
| api_tokens | 0 | Created by real login. Do not fake bearer tokens. |

## Lecturer feedback

None was uploaded. No feedback items can be marked done or outstanding until that document is provided.

## Mapping website features to the Android app

Customer flows map to the existing API. Admin stays on the website unless a later brief requires a mobile admin.

| Website | Android |
| --- | --- |
| Home campaigns and featured products | Home dashboard |
| Shop, search, filters, product | Catalogue and product |
| Cart, checkout, confirmation | Cart and checkout |
| Orders and tracking | Orders and tracking |
| Wishlist | Wishlist |
| Notifications | Notifications |
| Profile and address | Profile and saved settings |
| Register, login, logout | Same, plus genuine SSO |
| About and contact | About and contact |

## Gaps against the Task 2 brief

1. No Android Studio project, APK instructions, or device test evidence.
2. No genuine SSO. Email and password login is real, but it is not OAuth or OpenID Connect.
3. No GitHub repository, GitHub Actions, or Azure DevOps records.
4. No automated tests or testing report.
5. Documentation is only `README.md`. User manual, WampServer guide, Android Studio guide, API docs, demo script, and compliance checklist are missing.
6. Several transactional tables have no seed data.
7. Product photography referenced by the seed file is missing from the repo.
8. WampServer is not installed, so local Apache, PHP, and MySQL have not been verified on this machine.
9. `config/database.php` uses root with an empty password, which matches a default local Wamp install and must stay out of any public host.
10. Physical-device testing has not been done.

## Assumptions until you say otherwise

- Keep the PHP, Apache, and MySQL architecture. Do not rewrite the site in another framework.
- Android app is Kotlin with Jetpack Compose, calling the existing JSON API. Minimum SDK 26, target SDK aligned with the installed platform 37.
- On a phone, the API base URL is the computer’s LAN address (for example `http://192.168.x.x/art-on-denim/api/`), never `localhost`.
- SSO provider: Google OpenID Connect, authorisation code with PKCE on Android, and the same provider on the website. This needs a Google Cloud OAuth client you create. No client secret will be placed in the Android app. Until those client IDs exist, SSO can be coded and documented but cannot be marked as verified.
- No paid hosting or paid identity products will be added without asking.
- Admin console remains a website feature.

## Implementation checklist

### Done in Task 1 (preserve)

- [x] Project name, purpose, ZAR commerce, customer and admin roles
- [x] Storefront pages listed above
- [x] Admin modules listed above
- [x] Relational schema with foreign keys
- [x] Hashed passwords and session login
- [x] JSON API for auth, catalogue, cart, wishlist, orders, tracking, notifications
- [x] Design tokens for denim, chalk, and brass

### Website upgrade

- [ ] Substantial visual upgrade using the existing design system
- [ ] Responsive desktop, tablet, and phone layouts rechecked
- [ ] Every visible control still performs a real action
- [ ] Registration, login, logout, profile, and settings still work
- [ ] Catalogue, cart, checkout, orders, and admin flows retested on Apache

### Backend, database, auth

- [ ] WampServer installed and project served from `www`
- [ ] Database imported and connection verified
- [ ] Seed data raised to at least 10 valid rows on each required table
- [ ] API reachable from the LAN for a physical phone
- [ ] Google SSO on the website and Android, server-side verification
- [ ] Secrets only in local config templates, not committed

### Android

- [ ] Android Studio project with Compose, navigation, view models, repositories
- [ ] Splash, register, login, SSO, home, catalogue, product, cart, checkout, orders, tracking, wishlist, notifications, profile, settings
- [ ] Validation, loading, empty, and error states
- [ ] Secure token storage
- [ ] Unit and UI tests
- [ ] Debug APK instructions
- [ ] Physical phone test recorded only after it actually runs

### DevOps and evidence

- [ ] Git repository with a secret-safe ignore list
- [ ] GitHub Actions build and test workflow
- [ ] Azure DevOps backlog file ready to import (live board updates only if access exists)
- [ ] Testing report that separates automated, Wamp, emulator, and phone results
- [ ] User manual, Wamp guide, Android Studio guide, technical docs, demo script, compliance checklist

## Current status on 13 October 2026

The website and Android app share one store, one API, and the same customer flows: home, shop, product, bag, coupons, checkout, orders, tracking, saved pieces, notifications, profile, journal, about, contact, and the studio assistant. The app uses the chalk, ink, and brass colours and the same homepage wording.

Seed counts are at least 10 on every required table except `api_tokens`, which are created only when someone signs in. Google sign-in is implemented on both clients and stays hidden until `config/google.local.php` exists. It has not been demonstrated. The debug APK compiles and the unit tests pass on this PC. The app has not been installed on an emulator or a phone in this session. GitHub Actions is in the repo and has not run on GitHub until the project is pushed. The Azure file is an import file, not a live board update.

## Phase status

Phase 1 (analyse) is complete for the material that is actually in the project.

Phase 2 (audit) is started in the gap list above and continues when pages are executed against a running database.

Next: install and configure WampServer (Phase 3), then expand seed data and begin the website upgrade. Android Studio is already installed, so the app project can start once the API contract is frozen.

Blocking item that needs you: Google OAuth client ID (and website client secret kept only on the server) before SSO can be demonstrated. WampServer also needs to be installed on this PC before local website verification.
