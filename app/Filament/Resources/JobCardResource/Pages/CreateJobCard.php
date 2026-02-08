<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobCardResource\Pages;

use App\Filament\Resources\JobCardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobCard extends CreateRecord
{
    protected static string $resource = JobCardResource::class;
}
