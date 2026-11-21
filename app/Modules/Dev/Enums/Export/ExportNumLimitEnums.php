<?php

namespace App\Modules\Dev\Enums\Export;

enum ExportNumLimitEnums
{
    //  导出数据默认最大数量
    const COMMON_EXCEL_TOTAL_NUM_LIMIT = 10000;
    const COMMON_JSON_TOTAL_NUM_LIMIT = 20000;
    const COMMON_PAGE_NUM_LIMIT = 500;
}
