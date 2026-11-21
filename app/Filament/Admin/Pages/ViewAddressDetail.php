<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Modules\Domains\Address\Models\Address;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class ViewAddressDetail extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected string $view = 'filament.pages.address-detail';

    protected static ?string $title = '地址详情';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|UnitEnum|null $navigationGroup = '地址管理';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'address';

    public static function getRoutePath(Panel $panel): string
    {
        return '/address/{id}';
    }

    public ?Address $address = null;

    public array $parentChain = [];

    public int $childCount = 0;

    public int $totalChildCount = 0;

    /** @var Collection */
    public $children;

    /** @var Collection */
    public $siblings;

    public int $siblingCount = 0;

    public function mount(int|string $id): void
    {
        $address = Address::with('parent')->find($id);

        if (! $address) {
            abort(404, '地址不存在');
        }

        $this->address = $address;
        $this->parentChain = $this->buildParentChain($address);
        $this->childCount = $address->children()->count();
        $this->totalChildCount = $this->countAllDescendants($address->id);
        $this->children = $address->children()->limit(20)->get();
        $this->siblings = Address::where('parent_id', $address->parent_id)
            ->where('id', '!=', $address->id)
            ->orderBy('sort')
            ->orderBy('name')
            ->limit(10)
            ->get();
        $this->siblingCount = $this->siblings->count();
    }

    protected function buildParentChain(Address $address): array
    {
        $chain = [];
        $current = $address->parent;

        while ($current) {
            $chain[] = $current;
            $current = $current->parent;
        }

        return array_reverse($chain);
    }

    protected function countAllDescendants(int $parentId): int
    {
        $count = 0;
        $childIds = Address::where('parent_id', $parentId)->pluck('id');

        foreach ($childIds as $childId) {
            $count++;
            $count += $this->countAllDescendants($childId);
        }

        return $count;
    }
}
