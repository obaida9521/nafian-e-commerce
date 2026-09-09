<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') — {{ config('shop.name', 'Nafian') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,600;9..40,700&family=Fira+Code:wght@500&display=swap" rel="stylesheet">
    <style>
        body { margin:0; font-family:'DM Sans',system-ui,sans-serif; background:#fff2e3; color:#111;
               min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .card { text-align:center; max-width:440px; }
        .brand { font-weight:700; font-size:20px; letter-spacing:0.24em; color:#691d2a; margin-bottom:28px; }
        .code { font-family:'Fira Code',monospace; font-size:72px; font-weight:500; color:#691d2a; line-height:1; margin:0; }
        h1 { font-size:24px; font-weight:600; margin:16px 0 8px; }
        p { color:#6B7280; font-size:15px; line-height:1.6; margin:0 0 28px; }
        a.btn { display:inline-block; background:#691d2a; color:#fff; text-decoration:none; font-size:14px;
                font-weight:600; padding:13px 26px; border-radius:8px; }
        a.btn:hover { background:#4d141e; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">NAFIAN</div>
        <p class="code">@yield('code')</p>
        <h1>@yield('headline')</h1>
        <p>@yield('message')</p>
        <a class="btn" href="{{ url('/') }}">Back to home</a>
    </div>
</body>
</html>
