<?php

declare(strict_types=1);

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVehicle extends ViewRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->hasPermission('edit-vehicles')),
        ];
    }
}
