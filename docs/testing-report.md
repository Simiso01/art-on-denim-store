# Testing report

Date: 13 October 2026

## Automated

`gradlew :app:testDebugUnitTest` was run on this PC after the app was rebuilt. The tests cover rand formatting and the same checkout checks the website uses: an empty form is rejected, and a Western Cape address with Payfast is accepted.

PHP files were loaded through Apache. `http://localhost/art-on-denim/api/products.php?action=meta` returned success, ten categories, ten brands, the nine provinces, shipping of 99, and the free-shipping threshold of 1500. Google was false because no client file exists.

## Wamp

The homepage was already confirmed on Apache earlier in this work. The new API files were copied into `C:\wamp64\www\art-on-denim` and the meta request above was answered by that Apache.

`database/seed_minimum.php` printed counts of at least 10 for users, categories, brands, products, product images, inventory, carts, cart items, wishlist, orders, order items, payments, promotions, notifications, contact messages, order history, and journal posts. `api_tokens` was left empty on purpose.

## Emulator

The debug APK was assembled. It was not installed on the Medium Phone emulator in this session, so emulator behaviour is not claimed.

## Phone

No physical phone was used. No phone result is claimed.
