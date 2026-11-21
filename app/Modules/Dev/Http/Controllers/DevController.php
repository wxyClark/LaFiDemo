<?php

namespace App\Modules\Dev\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dev\Services\Dev\QueryService;

class DevController extends Controller
{
    protected $queryService;
    public function __construct(QueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    /**
     * DEV首页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @author wxyClark
     * @create 2025/11/29 17:29
     *
     * @version 1.0
     */
    public function query()
    {
        return view('dev.query');
    }
}
