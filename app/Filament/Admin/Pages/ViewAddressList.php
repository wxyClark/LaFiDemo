<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\HasExportAction;
use App\Modules\Domains\Address\Services\AddressService;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ViewAddressList extends Page
{
    use HasExportAction;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected string $view = 'filament.pages.address-list';

    protected static ?string $title = '地址信息浏览';

    protected static string|UnitEnum|null $navigationGroup = '地址管理';

    protected static ?int $navigationSort = 0;

    public ?int $selectedCountryId = null;

    public ?int $selectedProvinceId = null;

    public ?int $selectedCityId = null;

    public ?int $selectedDistrictId = null;

    public ?int $selectedTownshipId = null;

    public int $page = 1;

    public int $perPage = 25;

    public int $totalResults = 0;

    public array $countries = [];

    public array $provinces = [];

    public array $cities = [];

    public array $districts = [];

    public array $townships = [];

    public function mount(): void
    {
        $this->loadFilterOptions();
        $this->updateTotalResults();
    }

    protected function loadFilterOptions(): void
    {
        $service = app(AddressService::class);

        $this->countries = $service->getChildrenByParentId(null)
            ->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])
            ->toArray();

        $this->provinces = $this->selectedCountryId
            ? $service->getChildrenByParentId($this->selectedCountryId)
                ->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])
                ->toArray()
            : [];

        $this->cities = $this->selectedProvinceId
            ? $service->getChildrenByParentId($this->selectedProvinceId)
                ->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])
                ->toArray()
            : [];

        $this->districts = $this->selectedCityId
            ? $service->getChildrenByParentId($this->selectedCityId)
                ->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])
                ->toArray()
            : [];

        $this->townships = $this->selectedDistrictId
            ? $service->getChildrenByParentId($this->selectedDistrictId)
                ->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])
                ->toArray()
            : [];
    }

    public function updatedSelectedCountryId(): void
    {
        $this->selectedProvinceId = null;
        $this->selectedCityId = null;
        $this->selectedDistrictId = null;
        $this->selectedTownshipId = null;
        $this->page = 1;
        $this->loadFilterOptions();
        $this->updateTotalResults();
    }

    public function updatedSelectedProvinceId(): void
    {
        $this->selectedCityId = null;
        $this->selectedDistrictId = null;
        $this->selectedTownshipId = null;
        $this->page = 1;
        $this->loadFilterOptions();
        $this->updateTotalResults();
    }

    public function updatedSelectedCityId(): void
    {
        $this->selectedDistrictId = null;
        $this->selectedTownshipId = null;
        $this->page = 1;
        $this->loadFilterOptions();
        $this->updateTotalResults();
    }

    public function updatedSelectedDistrictId(): void
    {
        $this->selectedTownshipId = null;
        $this->page = 1;
        $this->loadFilterOptions();
        $this->updateTotalResults();
    }

    public function updatedSelectedTownshipId(): void
    {
        $this->page = 1;
        $this->updateTotalResults();
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function resetFilters(): void
    {
        $this->selectedCountryId = null;
        $this->selectedProvinceId = null;
        $this->selectedCityId = null;
        $this->selectedDistrictId = null;
        $this->selectedTownshipId = null;
        $this->page = 1;
        $this->loadFilterOptions();
        $this->updateTotalResults();
    }

    protected function updateTotalResults(): void
    {
        $service = app(AddressService::class);
        $filters = $this->getFilters();
        $this->totalResults = $service->buildQuery($filters)->count();
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function getFilters(): array
    {
        $filters = [];

        if ($this->selectedTownshipId) {
            $filters['parent_id'] = $this->selectedTownshipId;
        } elseif ($this->selectedDistrictId) {
            $filters['parent_id'] = $this->selectedDistrictId;
        } elseif ($this->selectedCityId) {
            $filters['parent_id'] = $this->selectedCityId;
        } elseif ($this->selectedProvinceId) {
            $filters['parent_id'] = $this->selectedProvinceId;
        } elseif ($this->selectedCountryId) {
            $filters['parent_id'] = $this->selectedCountryId;
        }

        return $filters;
    }

    public function getFilteredAddresses(): Paginator
    {
        $service = app(AddressService::class);
        $filters = $this->getFilters();

        return $service->buildQuery($filters)
            ->orderBy('id')
            ->simplePaginate($this->perPage, ['*'], 'page', $this->page);
    }

    // ── HasExportAction 实现 ──────────────────────────────

    public function getExportQuery(): Builder
    {
        $service = app(AddressService::class);

        return $service->buildQuery($this->getFilters());
    }

    public function getExportColumns(): array
    {
        return [
            'id' => 'ID',
            'name' => '名称',
            'code' => '行政区划代码',
            'level' => '层级',
            'parent_name' => '上级地址',
            'pinyin' => '拼音',
        ];
    }

    public function getExportLabel(): string
    {
        return '地址数据';
    }

    protected function getExportDirectory(): string
    {
        return 'exports/addresses';
    }
}
