<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset password – NooRly</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; margin: 0; padding: 2rem; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8fafc; }
        .card { max-width: 400px; width: 100%; background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        h1 { margin: 0 0 1.5rem; font-size: 1.5rem; color: #1e293b; }
        .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem; }
        .alert-error { background: #fef2f2; color: #b91c1c; }
        .alert-success { background: #f0fdf4; color: #166534; }
        label { display: block; margin-bottom: 0.25rem; font-weight: 500; color: #334155; font-size: 0.875rem; }
        input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; margin-bottom: 1rem; }
        input:focus { outline: none; border-color: #0ea5e9; }
        button { width: 100%; padding: 0.75rem; background: #0f766e; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #0d9488; }
        .text-sm { font-size: 0.875rem; color: #64748b; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Reset password</h1>

        @if(session('reset_error'))
            <div class="alert alert-error" role="alert">{{ session('reset_error') }}</div>
        @endif
        @if(session('reset_success'))
            <div class="alert alert-success" role="alert">{{ session('reset_success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error" role="alert">
                <ul style="margin:0;padding-left:1.25rem;">
                    @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('password.reset.submit') }}">
            @csrf
            <input type="hidden" name="token" value="{{ old('token', $token) }}">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" placeholder="you@example.com">
            <label for="password">New password</label>
            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters">
            <label for="password_confirmation">Confirm new password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
            <button type="submit">Reset password</button>
        </form>

        <p class="text-sm">If you didn’t request this, you can ignore this page. The link expires in 60 minutes.</p>
    </div>
</body>
</html>
