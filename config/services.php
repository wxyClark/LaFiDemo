<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'address' => [
        'amap_key' => env('AMAP_KEY', ''),
        'amap_base_url' => env('ADDRESS_API_BASE_URL', 'https://restapi.amap.com'),
        'import_base_url' => env('ADDRESS_IMPORT_BASE_URL'),
        // 民政部 · 中国·国家地名信息库（dmfw.mca.gov.cn）行政区划接口
        'mca_base_url' => env('ADDRESS_MCA_BASE_URL', 'https://dmfw.mca.gov.cn/9095'),
        'mca_xzqh_endpoint' => env('ADDRESS_MCA_XZQH_ENDPOINT', '/xzqh/getList'),
        // PHP 默认无 CA（curl.cainfo 为空），且部分环境存在 SSL 拦截/自签名中间证书，
        // 无法校验证书链时置 ADDRESS_MCA_VERIFY=false（仅读取公开行政区划数据，非敏感）。
        'mca_verify' => filter_var(env('ADDRESS_MCA_VERIFY', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'filament_admin' => [
        'prefill_email' => env('FILAMENT_ADMIN_PREFILL_EMAIL'),
        'prefill_password' => env('FILAMENT_ADMIN_PREFILL_PASSWORD'),
    ],

];
