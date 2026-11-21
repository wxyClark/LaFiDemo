<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>权限管理 - 超级管理员后台</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; color: #333; }
        .nav { margin-top: 10px; }
        .nav a { margin-right: 20px; color: #666; text-decoration: none; }
        .nav a:hover { color: #007bff; }
        .content { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>权限管理 - 超级管理员后台</h1>
            <div class="nav">
                <a href="/super/users">用户管理</a>
                <a href="/super/roles">角色管理</a>
                <a href="/super/permissions">权限管理</a>
                <a href="/super/audit-logs">审计日志</a>
            </div>
        </div>
        <div class="content">
            <h2>欢迎使用权限管理系统</h2>
            <p>这是超级管理员后台，用于管理所有系统的用户、角色和权限分配。</p>
        </div>
    </div>
</body>
</html>
