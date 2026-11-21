<?php

namespace App\Modules\Dev\Enums;

enum ErrorCodeEnums
{
    const ERROR_CODE_CLIENT_PARAMS = 400;

    const ERROR_CODE_CLIENT_AUTH = 401;

    const ERROR_CODE_CLIENT_PERMISSION = 403;

    const ERROR_CODE_RESOURCE_NOT_FOUND = 404;

    const ERROR_CODE_SERVER = 500;

    const ERROR_CODE_SERVICE_UNAVAILABLE = 503;

    const ERROR_CODE_MAP = [
        //  客户端错误
        self::ERROR_CODE_CLIENT_PARAMS => 'Bad Request',
        self::ERROR_CODE_CLIENT_AUTH => 'Unauthorized',
        self::ERROR_CODE_CLIENT_PERMISSION => 'Forbidden',
        self::ERROR_CODE_RESOURCE_NOT_FOUND => 'Resource Not Found',

        //  服务端错误
        self::ERROR_CODE_SERVER => 'Internal Server Error',
        self::ERROR_CODE_SERVICE_UNAVAILABLE => 'Service Unavailable',

        //  自定义错误
    ];
}
