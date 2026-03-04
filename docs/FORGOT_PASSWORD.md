# Forgot Password Flow

## Overview

- **Backend (Laravel)**: `POST /api/v1/auth/forgot-password`, `POST /api/v1/auth/reset-password`
- **Frontend (Flutter)**: Forgot Password screen → email → Reset Password screen (deep link or manual token)
- **Deep link**: `myapp://reset-password?email=...&token=...`

---

## Backend (Laravel)

### Env

- **Mail**: Configure `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` so reset emails can be sent. For local dev, `MAIL_MAILER=log` writes to `storage/logs/laravel.log`.
- **Reset link URL**: Optional.
  - `APP_PASSWORD_RESET_SCHEME` (default: `myapp`) → scheme in the link.
  - `APP_PASSWORD_RESET_PATH` (default: `reset-password`) → path segment.
  - Resulting link: `{scheme}://{path}?email=...&token=...` (e.g. `myapp://reset-password?email=user@example.com&token=...`).

### Throttling

- **Forgot password**: `throttle:5,1` (5 requests per minute per IP). 429 responses when exceeded.

### Security

- Same success response for forgot-password whether the email exists or not.
- Tokens are never logged. Avoid logging request bodies for these endpoints in production.

---

## Frontend (Flutter)

### Deep link

- **Format**: `myapp://reset-password?email=<encoded_email>&token=<token>`
- **Parsing**: `lib/core/deep_link/deep_link_handler.dart` → `parseResetPasswordPath(Uri)` returns `/reset-password?email=...&token=...` or `null`.
- **Android**: `android/app/src/main/AndroidManifest.xml` – intent-filter with `scheme="myapp"`, `host="reset-password"`.
- **iOS**: `ios/Runner/Info.plist` – `CFBundleURLTypes` with `CFBundleURLSchemes` = `myapp`.

Opening the link (from email or browser) launches the app and navigates to Reset Password with email and token prefilled.

### Manual token (e.g. dev)

- Open `/reset-password` (no query params) and enter email + token from the reset email (or logs when using `MAIL_MAILER=log`).

---

## Flow

1. User taps **Forgot Password?** on Login → Forgot Password screen.
2. Enters email → **Send Reset Link** → backend sends email (or no-op if email not found); UI shows “Check your email”.
3. User opens link in email → app opens on Reset Password with email/token set, or user goes to Reset Password and enters them.
4. User sets new password + confirm → **Reset Password** → success toast and redirect to Login.
