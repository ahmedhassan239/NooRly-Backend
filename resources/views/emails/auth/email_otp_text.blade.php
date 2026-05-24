Hello,

@if (($purpose ?? 'email_verification') === 'password_reset')
Please use the following OTP code to reset your password. This code is valid for 10 minutes.
@else
Please use the following OTP code to verify your email address. This code is valid for 10 minutes.
@endif

OTP Code: {{ $otp }}

If you did not request this code, please ignore this email.

Thanks,
ق Team
