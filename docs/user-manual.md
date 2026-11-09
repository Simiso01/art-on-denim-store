# Art on Denim user manual

Art on Denim is one store. The website and the Android app use the same catalogue, prices in rand, accounts, bag, saved pieces, orders, and journal.

## Website

Open `http://localhost/art-on-denim/` while Wamp is running.

- Shop, New, Brands, Sale, Journal, About, and Contact are in the header.
- A product page shows sizes and related pieces. Add a size to the bag or save it.
- The bag accepts the coupon codes already in the store, including WELCOME10.
- Checkout asks for a South African address and Payfast, EFT, or card. This local build records the order. It does not charge a real card.
- Orders and tracking are under the account. Tracking also accepts an order number and the email used at checkout.
- Ask the studio answers from store information. It is not an outside chatbot.

Demo accounts already in the database use the password `Password123!`:

- admin@artondenim.co.za
- lebo@example.com

Extra seed customers use that same password. Their addresses are `*.seed@example.com`.

## Android

Open the `android` folder in Android Studio and run it. Sign in with the same email and password. The bag, saved list, checkout, orders, journal, contact form, and studio chat call the same API as the website.

Google sign-in appears only after `config/google.local.php` is filled in. Until then both the website and the app show the same notice.
