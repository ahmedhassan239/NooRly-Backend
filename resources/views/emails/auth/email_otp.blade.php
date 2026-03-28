<!DOCTYPE html>
<html>
<head>
    <title>NooRly Security Code</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background-color: #f4f4f4; padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
        .content { padding: 20px; }
        .otp-code { font-size: 32px; font-weight: bold; color: #2c3e50; text-align: center; margin: 20px 0; letter-spacing: 5px; }
        .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>NooRly Security Code</h2>
        </div>
        <div class="content">
            <p>Hello,</p>
            @if (($purpose ?? 'email_verification') === 'password_reset')
                <p>Please use the following OTP code to reset your password. This code is valid for 10 minutes.</p>
            @else
                <p>Please use the following OTP code to verify your email address. This code is valid for 10 minutes.</p>
            @endif
            
            <div class="otp-code">{{ $otp }}</div>
            
            <p>If you did not request this code, please ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} NooRly. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
