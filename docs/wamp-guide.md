# WampServer

This PC is running WampServer 3.2.0, Apache 2.4.41, and PHP 7.4.0. The tray icon does not open a menu on this Windows install. Use the services instead.

The site must live at `C:\wamp64\www\art-on-denim`, with `index.php` directly inside that folder. The address is `http://localhost/art-on-denim/`.

Database `art_on_denim` is on `127.0.0.1` port `3306`, user `root`, empty password. That matches this local Wamp install. Do not publish that password.

After copying PHP changes into `www`, reload `http://localhost/art-on-denim/`.

To top up the database:

`C:\wamp64\bin\php\php7.4.0\php.exe database\seed_minimum.php`

Apache is the `wampapache64` service. Starting it needs an administrator approval when the tray menu is unavailable.

A phone on the same network uses the PC's LAN address, for example `http://192.168.1.20/art-on-denim/`. `localhost` on the phone is the phone itself.
