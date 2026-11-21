<?php

declare(strict_types=1);

namespace App\Modules\Domains\Address\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Domains\Address\Enums\AddressLevel;
use App\Modules\Domains\Address\Models\Address;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AddressPublicController extends Controller
{
    public function index(): View
    {
        $stats = Cache::remember('address:public:stats', 3600, fn () => Address::query()
            ->whereIn('level', [
                AddressLevel::PROVINCE->value,
                AddressLevel::CITY->value,
                AddressLevel::DISTRICT->value,
            ])
            ->groupBy('level')
            ->selectRaw('level, count(*) as total')
            ->pluck('total', 'level')
            ->toArray());

        $provinces = Cache::remember('address:public:provinces', 3600, fn () => Address::with(['children.children' => function ($q) {
            $q->orderBy('sort');
        }])->where('level', AddressLevel::PROVINCE->value)->orderBy('sort')->get(['id', 'name', 'code'])->toArray());

        return view('address.index', [
            'total' => array_sum($stats),
            'provinceCount' => $stats['province'] ?? 0,
            'cityCount' => $stats['city'] ?? 0,
            'districtCount' => $stats['district'] ?? 0,
            'provinces' => $provinces,
        ]);
    }
}
