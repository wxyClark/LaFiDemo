<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册 - {{ config('app.name', 'Laravel') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #667eea;
            font-size: 28px;
            font-weight: 700;
        }
        .logo p {
            color: #666;
            font-size: 14px;
            margin-top: 8px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-register:active {
            transform: translateY(0);
        }
        .error-message {
            background: #fee;
            border-left: 4px solid #f56565;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .error-message ul {
            list-style: none;
            color: #c53030;
            font-size: 14px;
        }
        .error-message li {
            margin: 4px 0;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h1>{{ config('app.name', 'Laravel') }}</h1>
            <p>创建新账户，开始您的旅程</p>
        </div>

        @if ($errors->any())
            <div class="error-message">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            
            <div class="form-group">
                <label for="name">姓名</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="请输入姓名">
            </div>

            <div class="form-group">
                <label for="email">邮箱地址</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="请输入邮箱">
            </div>

            <div class="form-group">
                <label for="password">密码</label>
                <input id="password" type="password" name="password" required placeholder="请输入密码（至少8位）">
            </div>

            <div class="form-group">
                <label for="password_confirmation">确认密码</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required placeholder="请再次输入密码">
            </div>

            <button type="submit" class="btn-register">注册</button>
        </form>

        <div class="login-link">
            已有账号？<a href="{{ route('login') }}">立即登录</a>
        </div>
    </div>
</body>
</html>
