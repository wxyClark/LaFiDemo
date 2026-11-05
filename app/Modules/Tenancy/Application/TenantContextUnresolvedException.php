<?php

namespace App\Modules\Tenancy\Application;

use RuntimeException;

/**
 * 可信租户上下文不可解析：配置未设置且显式 API 未被调用。
 * 默认拒绝（testing-security.md §3）。
 */
final class TenantContextUnresolvedException extends RuntimeException {}
