<?php

declare(strict_types=1);

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->hasPermission('create-vehicles')),
        ];
    }
}
