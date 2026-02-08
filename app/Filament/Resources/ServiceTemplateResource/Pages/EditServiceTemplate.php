<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceTemplateResource\Pages;

use App\Filament\Resources\ServiceTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceTemplate extends EditRecord
{
    protected static string $resource = ServiceTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
