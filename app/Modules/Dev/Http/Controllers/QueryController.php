<?php

namespace App\Modules\Dev\Http\Controllers;


use App\Modules\Dev\Services\Dev\QueryService;
use Illuminate\Http\Request;


/**
 * Created by PhpStorm
 * Author: wxyClark
 * Date: 2025/11/29
 * Time: 17:50
 * Email: C18666211369@outlook.com
 */
class QueryController
{

    protected $queryService;

    public function __construct(QueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function list(Request $request)
    {
        $validated = $request->validate([
            'sql' => 'required|string|max:2000',
            'page_size' => 'required|integer|min:1|max:200',
            'page' => 'nullable|integer|min:0',
            'user_id' => 'integer|min:1',
        ]);

        $validated['need_insert_query'] = empty($validated['page']) ? true : false;

        // 设置 page 参数的默认值为 1
        if (empty($validated['page']) || $validated['page'] < 1) {
            $validated['page'] = 1;
        }
        if ($validated['page'] > 100) {
            return [
                'status' => false,
                'error_msg' => 'The maximum number of pages supported is 100. Please modify the query criteria to obtain the data you need',
            ];
        }

        return $this->queryService->executeSql($validated);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'sql' => 'required|string|max:2000',
            'type' => 'required|string|in:excel,json',
        ]);

        return $this->queryService->export($validated);
    }
}
