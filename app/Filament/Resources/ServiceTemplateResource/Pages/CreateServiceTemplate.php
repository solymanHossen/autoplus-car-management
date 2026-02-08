<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceTemplateResource\Pages;

use App\Filament\Resources\ServiceTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceTemplate extends CreateRecord
{
    protected static string $resource = ServiceTemplateResource::class;
}
