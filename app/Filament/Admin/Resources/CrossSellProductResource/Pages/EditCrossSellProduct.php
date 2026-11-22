<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellProductResource\Pages;

use App\Filament\Admin\Resources\CrossSellProductResource;
use App\Modules\CrossSell\Product\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrossSellProduct extends EditRecord
{
    protected static string $resource = CrossSellProductResource::class;

    protected ?string $translationTitle = null;

    protected ?string $translationDescription = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record instanceof Product) {
            $translation = $this->record->translations()->where('language', 'en')->first();
            $data['title_en'] = $translation?->title;
            $data['description_en'] = $translation?->description;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->translationTitle = $data['title_en'] ?? null;
        $this->translationDescription = $data['description_en'] ?? null;
        unset($data['title_en'], $data['description_en']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record instanceof Product) {
            $this->record->syncTranslation('en', $this->translationTitle, $this->translationDescription);
        }
    }
}
