# Android Studio

1. Install Android Studio. This machine has Android Studio 2026.2.1 and SDK platform `android-37.0`.
2. File, Open, and choose the `android` folder inside the project.
3. Let Gradle sync. The wrapper uses Gradle 9.6.0. Java comes from Android Studio's `jbr` folder if Java is not on PATH.
4. Run the `app` configuration on the Medium Phone emulator. The API address is `http://10.0.2.2/art-on-denim/api/`.
5. For a physical phone, change `API_BASE` in `android/app/build.gradle.kts` to `http://YOUR-PC-LAN-IP/art-on-denim/api/` and rebuild. Do not use `localhost`.
6. Unit tests: `gradlew :app:testDebugUnitTest`.

The debug APK is written to `android/app/build/outputs/apk/debug/app-debug.apk` after a successful assemble.

Google sign-in uses the server OAuth client. Put the client id and secret in `config/google.local.php` from `config/google.local.php.example`. The secret is not stored in the app. The redirect URI is `http://localhost/art-on-denim/pages/google-callback.php`. After Google returns, the server hands the app a one-time code on `aod://auth`.
