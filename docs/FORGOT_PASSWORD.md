# Forgot Password Flow (Email OTP)

## Overview

- **Backend (Laravel)**
  - `POST /api/v1/auth/forgot-password/request-otp`
  - `POST /api/v1/auth/forgot-password/verify-otp`
  - `POST /api/v1/auth/forgot-password/reset`
- **Frontend (Flutter)**: Forgot Password screen -> Verify OTP -> Reset Password

## Security Notes

- Generic response on OTP request (no email enumeration).
- OTP is hashed in `email_otps.otp_hash` and scoped with `purpose=password_reset`.
- OTP expires in 10 minutes, is single-use (`used_at`), and max attempts is 5.
- Resend cooldown is 60 seconds.
- OTP verify step issues a short-lived reset token (10 minutes), hashed in `password_reset_otp_sessions.token_hash`.
- Password reset consumes the reset session and revokes all existing Sanctum tokens for the user.
- Only `provider=email` accounts with non-null password can use this reset flow.

## End-to-End Flow

1. User submits email to request OTP.
2. Backend sends OTP email if eligible account exists.
3. User submits 6-digit OTP.
4. Backend verifies OTP and returns short-lived `reset_token`.
5. User submits new password with `reset_token`.
6. Backend updates password, consumes reset session, revokes old access tokens.
7. App redirects user to login and requires sign in again.
