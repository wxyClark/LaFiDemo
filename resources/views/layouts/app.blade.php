<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
</head>
<body>
    <nav>
        <a href="/">{{ config('app.name', 'Laravel') }}</a>
        @auth('customer')
            <span>{{ Auth::guard('customer')->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit">退出</button>
            </form>
        @else
            <a href="{{ route('login') }}">登录</a>
            <a href="{{ route('register') }}">注册</a>
        @endauth
    </nav>
    <main>
        {{ $slot }}
    </main>
</body>
</html>
