<?php

namespace App\Modules\Domains\Address\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Domains\Address\Http\Requests\AddressChildrenRequest;
use App\Modules\Domains\Address\Services\AddressService;
use Illuminate\Http\JsonResponse;

class AddressApiController extends Controller
{
    public function __construct(
        protected AddressService $addressService
    ) {}

    /**
     * 获取所有地址（公开接口，无需认证）
     */
    public function index(): JsonResponse
    {
        $addresses = $this->addressService->getAllAddresses();

        return response()->json([
            'data' => $addresses,
        ]);
    }

    /**
     * 根据上级ID获取子级地址（公开接口）
     */
    public function children(AddressChildrenRequest $request): JsonResponse
    {
        $parentId = $request->validated('parent_id');
        $children = $this->addressService->getChildrenByParentId($parentId);

        return response()->json([
            'data' => $children,
        ]);
    }

    /**
     * 根据层级获取地址（公开接口）
     */
    public function byLevel(string $level): JsonResponse
    {
        $addresses = $this->addressService->getByLevel($level);

        return response()->json([
            'data' => $addresses,
        ]);
    }

    /**
     * 根据代码获取地址（公开接口）
     */
    public function findByCode(string $code): JsonResponse
    {
        $address = $this->addressService->findByCode($code);

        if (! $address) {
            return response()->json([
                'message' => '地址不存在',
            ], 404);
        }

        return response()->json([
            'data' => $address,
        ]);
    }

    /**
     * 获取地址树形结构（公开接口）
     */
    public function tree(): JsonResponse
    {
        $tree = $this->addressService->getAddressTree();

        return response()->json([
            'data' => $tree,
        ]);
    }
}
