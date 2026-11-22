<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellProductResource\Pages;

use App\Filament\Admin\Resources\CrossSellProductResource;
use App\Modules\CrossSell\Product\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateCrossSellProduct extends CreateRecord
{
    protected static string $resource = CrossSellProductResource::class;

    protected ?string $translationTitle = null;

    protected ?string $translationDescription = null;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->translationTitle = $data['title_en'] ?? null;
        $this->translationDescription = $data['description_en'] ?? null;
        unset($data['title_en'], $data['description_en']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record instanceof Product) {
            $this->record->syncTranslation('en', $this->translationTitle, $this->translationDescription);
        }
    }
}
