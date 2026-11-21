<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 薪福通 API 配置
    |--------------------------------------------------------------------------
    |
    | 招商银行薪福通开放平台 API 配置。
    | 账号信息存储在数据库 xinfutong_accounts 表中。
    |
    | 官方文档: https://xft.cmbchina.com/open/#/doc
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API 端点
    |--------------------------------------------------------------------------
    |
    | 薪福通 API 端点配置。
    | 根据实际文档调整这些端点。
    |
    */

    'endpoints' => [

        // 员工相关
        'employee_list' => '/api/v1/employee/list',
        'employee_detail' => '/api/v1/employee/detail',

        // 审批流相关
        'workflow_start' => '/api/v1/workflow/start',
        'workflow_status' => '/api/v1/workflow/status',
        'workflow_withdraw' => '/api/v1/workflow/withdraw',

        // 组织架构
        'org_list' => '/api/v1/org/list',

    ],

    /*
    |--------------------------------------------------------------------------
    | 签名配置
    |--------------------------------------------------------------------------
    |
    | 国密 SM2/SM3 签名相关配置。
    |
    */

    'signing' => [
        // 签名算法: sm2 (国密) 或 rsa (RSA-SHA256 回退)
        'algorithm' => env('XINFUTONG_SIGN_ALGORITHM', 'sm2'),

        // 时间戳格式: ms (毫秒) 或 s (秒)
        'timestamp_format' => 'ms',
    ],

];
