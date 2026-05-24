<!DOCTYPE html>
<html>
<head>
    <title>Reset your password</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background-color: #f4f4f4; padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
        .content { padding: 20px; }
        .button { display: inline-block; padding: 12px 24px; background-color: #1e40af; color: #fff !important; text-decoration: none; border-radius: 8px; margin: 16px 0; }
        .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>ق – Reset your password</h2>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>You requested a password reset. Use the link below to set a new password. This link is valid for {{ $expireMinutes }} minutes.</p>
            <p><a href="{{ $resetUrl }}" class="button">Reset password</a></p>
            <p>If the button does not work, copy and paste this link into your browser:</p>
            <p style="word-break: break-all; font-size: 12px; color: #666;">{{ $resetUrl }}</p>
            <p>If you did not request this, you can ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} ق. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
